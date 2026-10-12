<?php

namespace App\Http\Controllers\Api\Imports;

use App\Http\Controllers\Controller;
use App\Models\DataImport;
use App\Services\Importer\ImporterRegistry;
use App\Services\Importer\SpreadsheetReader;
use App\Services\Scoping\SchoolContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DataImportController extends Controller
{
    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:20480',
            'entity' => 'required|string|in:students,parents,teachers,classrooms,subjects,fees,attendances',
        ]);

        $importer = ImporterRegistry::get($request->entity);
        $reader = SpreadsheetReader::rowsFromUploadedFile($request->file('file'));
        $headers = $reader['headers'];
        $rows = $reader['rows'];
        $targetColumns = $importer->columns();
        $mapping = $this->autoMap($headers, $targetColumns);

        return ApiResponse::ok([
            'entity' => $request->entity,
            'entity_label' => $importer->label(),
            'source_columns' => $headers,
            'target_columns' => $targetColumns,
            'auto_mapping' => $mapping,
            'sample_rows' => array_slice($rows, 0, 5),
            'total_rows' => count($rows),
        ], 'تم قراءة الملف ومعاينة البيانات');
    }

    public function run(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:20480',
            'entity' => 'required|string|in:students,parents,teachers,classrooms,subjects,fees,attendances',
            'mapping' => 'required|array',
            'default_password' => 'nullable|string|max:255',
        ]);

        $actor = Auth::guard('api')->user() ?? Auth::guard('web')->user();
        $schoolId = SchoolContext::effectiveSchoolId($actor);

        $importer = ImporterRegistry::get($request->entity);
        $reader = SpreadsheetReader::rowsFromUploadedFile($request->file('file'));
        $headers = $reader['headers'];
        $rows = $reader['rows'];
        $mapping = $request->input('mapping');

        $mappedRows = array_map(function (array $row) use ($mapping, $headers, $importer) {
            $canonical = [];
            foreach ($importer->columns() as $col) {
                $canonical[$col['key']] = $col['key'];
            }
            $normalized = [];
            foreach ($mapping as $targetKey => $sourceHeader) {
                foreach ($canonical as $canonKey) {
                    if (strcasecmp((string) $targetKey, (string) $canonKey) === 0 || strtolower((string) $targetKey) === strtolower((string) $canonKey)) {
                        $normalized[$canonKey] = $sourceHeader;
                        break;
                    }
                }
                if (! isset($normalized[$targetKey])) {
                    $normalized[$targetKey] = $sourceHeader;
                }
            }
            $mapped = [];
            foreach ($normalized as $targetKey => $sourceHeader) {
                $sourceIdx = array_search($sourceHeader, $headers);
                $mapped[$targetKey] = $sourceIdx !== false ? ($row[$headers[$sourceIdx]] ?? '') : '';
            }
return $mapped;
            }, $rows);

        $errors = [];
        $imported = 0;
        $skipped = 0;

        DB::beginTransaction();
        try {
            foreach ($mappedRows as $i => $row) {
                $rowNum = $i + 2;
                $validationErrors = $importer->validate($row, (int) $schoolId);
                if ($validationErrors !== null) {
                    $errors[] = ['row' => $rowNum, 'errors' => $validationErrors];
                    $skipped++;
                    continue;
                }
                try {
                    $importer->store($row, (int) $schoolId);
                    $imported++;
                } catch (\Throwable $e) {
                    $errors[] = ['row' => $rowNum, 'errors' => [$e->getMessage()]];
                    $skipped++;
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'فشلت العملية: ' . $e->getMessage(),
            ], 500);
        }

        $dataImport = DataImport::create([
            'school_id' => $schoolId,
            'user_id' => $actor?->id,
            'entity' => $request->entity,
            'file_name' => $request->file('file')->store('imports'),
            'original_name' => $request->file('file')->getClientOriginalName(),
            'status' => $skipped === 0 ? 'completed' : ($imported === 0 ? 'failed' : 'completed'),
            'total_rows' => count($mappedRows),
            'imported_rows' => $imported,
            'skipped_rows' => $skipped,
            'errors' => $errors,
            'report' => [
                'entity' => $importer->label(),
                'imported' => $imported,
                'skipped' => $skipped,
                'total' => count($mappedRows),
                'errors_count' => count($errors),
            ],
        ]);

        \App\Services\AuditLogger::log(
            'data_import',
            'App\Models\DataImport',
            $dataImport->id,
            "استيراد {$importer->label()}: {$imported} سجل مستورد، {$skipped} متخطّى",
            ['report' => $dataImport->report, 'errors_sample' => array_slice($errors, 0, 20)]
        );

        return ApiResponse::ok([
            'data_import_id' => $dataImport->id,
            'report' => $dataImport->report,
            'row_errors' => $errors,
            'imported' => $imported,
            'skipped' => $skipped,
        ], 'تم تنفيذ الاستيراد');
    }

    public function index(Request $request): JsonResponse
    {
        $actor = Auth::guard('api')->user() ?? Auth::guard('web')->user();
        $schoolId = SchoolContext::effectiveSchoolId($actor);

        $query = DataImport::orderByDesc('created_at');
        if ($schoolId) $query->where('school_id', $schoolId);

        $imports = $query->paginate(20);

        return ApiResponse::paginate($imports, 'تم استلام سجلات الاستيراد');
    }

    public function show(DataImport $import): JsonResponse
    {
        $actor = Auth::guard('api')->user();
        $schoolId = SchoolContext::effectiveSchoolId($actor);
        if ($schoolId && $import->school_id != $schoolId) {
            abort(403);
        }
        return ApiResponse::ok($import, 'تم استلام تفاصيل الاستيراد');
    }

    public function entities(): JsonResponse
    {
        return ApiResponse::ok(ImporterRegistry::supported(), 'تم استلام الكيانات المدعومة');
    }

    private function autoMap(array $headers, array $targetColumns): array
    {
        $mapping = [];
        $norm = fn(string $s) => preg_replace('/[^a-z0-9]/i', '', strtolower(trim($s)));
        $headerNorms = array_combine(array_map($norm, $headers), $headers);

        foreach ($targetColumns as $col) {
            $targetNorm = $norm($col['key']);
            $targetLabelNorm = isset($col['label']) ? $norm($col['label']) : '';

            if (isset($headerNorms[$targetNorm])) {
                $mapping[$col['key']] = $headerNorms[$targetNorm];
                continue;
            }

            if ($targetLabelNorm !== '' && isset($headerNorms[$targetLabelNorm])) {
                $mapping[$col['key']] = $headerNorms[$targetLabelNorm];
                continue;
            }

            foreach ($headerNorms as $hNorm => $hOrig) {
                if (str_contains($hNorm, $targetNorm) || str_contains($targetNorm, $hNorm)) {
                    $mapping[$col['key']] = $hOrig;
                    break;
                }
            }
        }

        return $mapping;
    }
}
