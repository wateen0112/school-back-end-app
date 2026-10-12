<?php

namespace App\Http\Controllers\Api\Schools;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Services\AuditLogger;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class SchoolController extends Controller
{
    /**
     * List all schools with their admin account and subscription info.
     */
    public function index(Request $request)
    {
        $query = School::with(['admin:id,name,email,school_id', 'subscription.plan:id,name,code', 'branches:id,school_id,name,is_active'])
            ->withCount('branches')
            ->orderByDesc('id');

        if ($request->boolean('include_deleted')) {
            $query->withTrashed();
        }

        $search = trim((string) $request->query('search'));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $status = $request->query('status');
        if ($status === 'suspended') {
            $query->suspended();
        } elseif ($status === 'archived') {
            $query->archived();
        } elseif ($status === 'deleted') {
            $query->onlyTrashed();
        } elseif ($status === 'active') {
            $query->active();
        }

        $schools = $query->paginate($request->integer('per_page', 20))->withQueryString();

        return response()->json([
            'success' => true,
            'message' => 'Schools retrieved successfully',
            'data' => collect($schools->items())->map(fn ($school) => $this->formatSchool($school)),
            'meta' => [
                'current_page' => $schools->currentPage(),
                'last_page' => $schools->lastPage(),
                'total' => $schools->total(),
            ],
        ], 200);
    }

    /**
     * Get a single school (by id).
     */
    public function show(Request $request, $id)
    {
        $school = School::withTrashed()->with(['admin:id,name,email,school_id', 'subscription.plan', 'branches'])->find($id);

        if (! $school) {
            return response()->json(['success' => false, 'message' => 'School not found'], 404);
        }

        return response()->json(['success' => true, 'message' => 'School retrieved successfully', 'data' => $this->formatSchool($school)], 200);
    }

    /**
     * Create a school and store its admin user's credentials so the admin
     * can log in through the web guard (users table) from day one.
     */
    public function store(Request $request, ?int $adminUserId = null)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'email' => 'required|email|max:255|unique:schools,email|unique:users,email',
            'admin_name' => 'nullable|string|max:255',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string',
            'status' => 'nullable|string|in:active,inactive,pending',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp,svg|max:4096',
            'theme' => 'nullable|array',
            'theme.primary' => ['nullable', 'string', 'regex:/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'theme.primary_dark' => ['nullable', 'string', 'regex:/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'theme.accent' => ['nullable', 'string', 'regex:/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $school = School::create([
                'name' => ['ar' => $request->name, 'en' => $request->name_en ?: $request->name],
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'status' => $request->status ?: 'active',
                'logo' => $request->hasFile('logo') ? $this->storeLogo($request->file('logo')) : null,
                'theme' => $request->has('theme') ? School::normalizeTheme($request->input('theme')) : School::THEME_DEFAULTS,
            ]);

            $user = User::create([
                'name' => $request->admin_name ?: $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'school_id' => $school->id,
                'is_super_admin' => false,
            ]);

            AuditLogger::log('school.created', 'School', $school->id, 'إنشاء مدرسة جديدة');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'School created successfully',
                'data' => [
                    'school' => $this->formatSchool($school),
                    'admin_email' => $user->email,
                    'admin_password' => $request->password,
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error creating school',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update school basic info (super admin only).
     */
    public function update(Request $request, $id)
    {
        $school = School::withTrashed()->find($id);

        if (! $school) {
            return response()->json(['success' => false, 'message' => 'School not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255|unique:schools,email,'.$school->id.'|unique:users,email',
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string',
            'status' => 'nullable|string|in:active,inactive,pending',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp,svg|max:4096',
            'remove_logo' => 'nullable|boolean',
            'theme' => 'nullable|array',
            'theme.primary' => ['nullable', 'string', 'regex:/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'theme.primary_dark' => ['nullable', 'string', 'regex:/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'theme.accent' => ['nullable', 'string', 'regex:/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        $oldName = $school->getTranslations('name');

        if ($request->filled('name') || $request->filled('name_en')) {
            $translations = $oldName;
            $translations['ar'] = $request->input('name', $translations['ar'] ?? '');
            $translations['en'] = $request->input('name_en', $translations['en'] ?? $translations['ar'] ?? '');
            $school->name = $translations;
        }

        $school->fill($request->only(['email', 'phone', 'address', 'status']));

        if ($request->hasFile('logo')) {
            if ($school->getOriginal('logo')) {
                $this->deleteLogoFile($school->getOriginal('logo'));
            }
            $school->logo = $this->storeLogo($request->file('logo'));
        } elseif ($request->boolean('remove_logo') && $school->getOriginal('logo')) {
            $this->deleteLogoFile($school->getOriginal('logo'));
            $school->logo = null;
        }

        if ($request->has('theme') && is_array($request->input('theme'))) {
            $school->theme = School::normalizeTheme(array_merge(
                $school->theme ?? [],
                $request->input('theme')
            ));
        }

        if ($request->input('email') && $request->input('email') !== $school->getOriginal('email')) {
            $school->email = $request->input('email');
            // keep the school admin user email in sync
            if ($admin = $school->admin) {
                $admin->update(['email' => $request->input('email')]);
            }
        }

        $school->save();

        AuditLogger::log('school.updated', 'School', $school->id, 'تعديل بيانات المدرسة');

        return response()->json(['success' => true, 'message' => 'School updated successfully', 'data' => $this->formatSchool($school)], 200);
    }

    /**
     * Update a school's branding (custom color theme + logo) — super admin only.
     * Kept separate from update() so the base info form and the branding form
     * stay independent.
     */
    public function updateBranding(Request $request, $id)
    {
        $school = School::withTrashed()->find($id);

        if (! $school) {
            return response()->json(['success' => false, 'message' => 'School not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'logo' => [
                'nullable',
                'max:4096',
                function ($attribute, $value, $fail) {
                    if (! $value instanceof \Illuminate\Http\UploadedFile) {
                        return;
                    }
                    if (! $value->isValid()) {
                        $fail('The logo failed to upload.');
                        return;
                    }
                    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/avif', 'image/svg+xml'];
                    try {
                        $mime = strtolower((string) $value->getMimeType());
                    } catch (\Throwable $e) {
                        $fail('The logo must be a valid image file.');
                        return;
                    }
                    if (! in_array($mime, $allowed, true)) {
                        $fail('The logo must be an image (jpg, jpeg, png, webp, avif, or svg).');
                        return;
                    }
                    if ($mime !== 'image/svg+xml' && @getimagesize($value->getRealPath()) === false) {
                        $fail('The logo must be a valid image file.');
                    }
                },
            ],
            'remove_logo' => 'nullable|boolean',
            'theme' => 'nullable|array',
            'theme.primary' => ['nullable', 'string', 'regex:/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'theme.primary_dark' => ['nullable', 'string', 'regex:/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'theme.accent' => ['nullable', 'string', 'regex:/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            if ($request->hasFile('logo')) {
                if ($school->getOriginal('logo')) {
                    $this->deleteLogoFile($school->getOriginal('logo'));
                }
                $school->logo = $this->storeLogo($request->file('logo'));
            } elseif ($request->boolean('remove_logo') && $school->getOriginal('logo')) {
                $this->deleteLogoFile($school->getOriginal('logo'));
                $school->logo = null;
            }

            if ($request->has('theme') && is_array($request->input('theme'))) {
                $school->theme = School::normalizeTheme(array_merge(
                    $school->theme ?? [],
                    $request->input('theme')
                ));
            }

            $school->save();

            AuditLogger::log('school.branding.updated', 'School', $school->id, 'تحديث الهوية البصرية للمدرسة');

            return response()->json([
                'success' => true,
                'message' => 'School branding updated successfully',
                'data' => $this->formatSchool($school),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating school branding',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Persist an uploaded logo into public/attachments/schools.
     */
    private function storeLogo($file): string
    {
        $mime = strtolower((string) $file->getMimeType());
        $extMap = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/avif' => 'avif',
            'image/svg+xml' => 'svg',
        ];
        $ext = $extMap[$mime] ?? strtolower((string) $file->getClientOriginalExtension());

        $base = preg_replace('/\.[^.]+$/', '', (string) $file->getClientOriginalName());
        $base = preg_replace('/\s+/', '_', $base);
        $name = time().'_'.mb_substr($base, 0, 60).'.'.$ext;

        $dir = public_path('attachments/schools');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $file->move($dir, $name);

        return $name;
    }

    /**
     * Remove a previously stored logo file.
     */
    private function deleteLogoFile(string $name): void
    {
        $path = public_path('attachments/schools/'.ltrim($name, '/'));
        if (is_file($path)) {
            @unlink($path);
        }
    }

    /**
     * Suspend a school (blocks the whole school).
     */
    public function suspend(Request $request, $id)
    {
        $school = School::find($id);

        if (! $school) {
            return response()->json(['success' => false, 'message' => 'School not found'], 404);
        }

        $school->update(['status' => 'inactive', 'suspended_at' => now()]);

        AuditLogger::log('school.suspended', 'School', $school->id, 'إيقاف المدرسة مؤقتاً');

        return response()->json(['success' => true, 'message' => 'School suspended successfully', 'data' => $this->formatSchool($school)], 200);
    }

    /**
     * Activate a school (or approve a pending one).
     */
    public function activate(Request $request, $id)
    {
        $school = School::withTrashed()->find($id);

        if (! $school) {
            return response()->json(['success' => false, 'message' => 'School not found'], 404);
        }

        $school->update(['status' => 'active', 'suspended_at' => null, 'archived_at' => null]);

        AuditLogger::log('school.activated', 'School', $school->id, 'تفعيل المدرسة');

        return response()->json(['success' => true, 'message' => 'School activated successfully', 'data' => $this->formatSchool($school)], 200);
    }

    /**
     * Archive a school (kept but moved out of the active set).
     */
    public function archive(Request $request, $id)
    {
        $school = School::find($id);

        if (! $school) {
            return response()->json(['success' => false, 'message' => 'School not found'], 404);
        }

        $school->update(['status' => 'inactive', 'archived_at' => now()]);

        AuditLogger::log('school.archived', 'School', $school->id, 'أرشفة المدرسة');

        return response()->json(['success' => true, 'message' => 'School archived successfully', 'data' => $this->formatSchool($school)], 200);
    }

    /**
     * Restore an archived school.
     */
    public function restore(Request $request, $id)
    {
        $school = School::withTrashed()->find($id);

        if (! $school) {
            return response()->json(['success' => false, 'message' => 'School not found'], 404);
        }

        if ($school->trashed()) {
            $school->restore();
        }

        $school->update(['archived_at' => null, 'status' => 'active']);

        AuditLogger::log('school.restored', 'School', $school->id, 'استعادة المدرسة');

        return response()->json(['success' => true, 'message' => 'School restored successfully', 'data' => $this->formatSchool($school)], 200);
    }

    /**
     * Delete a school following strict policies:
     *  - school must be archived first
     *  - school must not have branches
     *  - school must not have an active or trial subscription
     *  Deletion is soft (restorable by the super admin).
     */
    public function destroy(Request $request, $id)
    {
        $school = School::withTrashed()->find($id);

        if (! $school) {
            return response()->json(['success' => false, 'message' => 'School not found'], 404);
        }

        if ($school->trashed()) {
            // hard delete a soft-deleted school (final purge)
            $school->forceDelete();

            AuditLogger::log('school.deleted_forever', 'School', $id, 'حذف المدرسة نهائياً');

            return response()->json(['success' => true, 'message' => 'School deleted permanently'], 200);
        }

        $blockers = [];

        if (! $school->isArchived()) {
            $blockers[] = 'يجب أرشفة المدرسة قبل حذفها';
        }

        if ($school->branches()->exists()) {
            $blockers[] = 'لا يمكن حذف مدرسة تحتوي على فروع';
        }

        $subscription = $school->subscription;
        if ($subscription && in_array($subscription->status, ['trial', 'active'])) {
            $blockers[] = 'لا يمكن حذف مدرسة لديها اشتراك نشط أو تجريبي';
        }

        if (! empty($blockers)) {
            return response()->json([
                'success' => false,
                'message' => 'تعذر حذف المدرسة بسبب السياسات التالية',
                'errors' => $blockers,
            ], 422);
        }

        $school->delete();

        AuditLogger::log('school.deleted', 'School', $school->id, 'حذف المدرسة (أرشيف نهائي)');

        return response()->json(['success' => true, 'message' => 'School deleted successfully'], 200);
    }

    /**
     * Array shape consumed by the dashboard.
     */
    private function formatSchool(School $school)
    {
        $subscription = $school->relationLoaded('subscription') ? $school->subscription : null;

        return [
            'id' => $school->id,
            'name' => $school->getTranslations('name'),
            'email' => $school->email,
            'phone' => $school->phone,
            'address' => $school->address,
            'status' => $school->trashed() ? 'deleted' : ($school->status ?? 'active'),
            'suspended_at' => $school->suspended_at?->toISOString(),
            'archived_at' => $school->archived_at?->toISOString(),
            'deleted_at' => $school->deleted_at?->toISOString(),
            'is_suspended' => $school->isSuspended(),
            'is_archived' => $school->isArchived(),
            'logo_url' => $school->logoUrl(),
            'theme' => $school->theme,
            'admin_name' => $school->admin->name ?? null,
            'admin_email' => $school->admin->email ?? null,
            'branches_count' => $school->branches_count ?? $school->branches?->count() ?? 0,
            'subscription' => $subscription ? [
                'id' => $subscription->id,
                'status' => $subscription->status,
                'plan_id' => $subscription->plan_id,
                'plan_name' => $subscription->plan?->name ? $subscription->plan->getTranslations('name')['ar'] : null,
                'plan_code' => $subscription->plan?->code ?? null,
                'trial_ends_at' => $subscription->trial_ends_at?->toISOString(),
                'starts_at' => $subscription->starts_at?->toISOString(),
                'ends_at' => $subscription->ends_at?->toISOString(),
                'auto_renew' => $subscription->auto_renew,
            ] : null,
            'created_at' => $school->created_at?->toISOString(),
        ];
    }
}