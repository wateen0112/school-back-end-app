<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class QueryExecutorService
{
    protected SchemaService $schema;

    public function __construct(SchemaService $schema)
    {
        $this->schema = $schema;
    }

    /**
     * Execute a structured, validated query scoped to the given school.
     *
     * Supported query shape:
     * {
     *   table: string,
     *   columns?: string[],
     *   filters?: array{column: string, operator: string, value: mixed}[],
     *   aggregates?: array{column: string, function: string, alias?: string}[],
     *   group_by?: string[],
     *   order_by?: array{column: string, direction?: string}[],
     *   limit?: int,
     * }
     */
    public function execute(array $query, ?int $schoolId): array
    {
        $table = $query['table'] ?? null;

        if (! is_string($table) || ! $this->schema->isTableAllowed($table)) {
            throw new AiServiceException("Table '{$table}' is not allowed or does not exist.");
        }

        $columns = $this->normalizeColumns($query['columns'] ?? ['*'], $table);
        $queryBuilder = DB::table($table)->select($columns);

        $this->applySchoolScope($queryBuilder, $table, $schoolId);
        $this->applyFilters($queryBuilder, $query['filters'] ?? [], $table);
        $this->applyAggregates($queryBuilder, $query['aggregates'] ?? [], $table, $columns);
        $this->applyGroupBy($queryBuilder, $query['group_by'] ?? [], $table);
        $this->applyOrderBy($queryBuilder, $query['order_by'] ?? [], $table);
        $this->applyLimit($queryBuilder, $query['limit'] ?? null);

        return $queryBuilder->get()->toArray();
    }

    protected function normalizeColumns(array $columns, string $table): array
    {
        if ($columns === ['*'] || $columns === []) {
            return ['*'];
        }

        $allowed = [];
        foreach ($columns as $col) {
            if (! is_string($col)) {
                continue;
            }

            if ($col === '*') {
                return ['*'];
            }

            if (! $this->schema->isColumnAllowed($table, $col)) {
                throw new AiServiceException("Column '{$col}' is not allowed on table '{$table}'.");
            }

            $allowed[] = $col;
        }

        return $allowed ?: ['*'];
    }

    protected function applySchoolScope($queryBuilder, string $table, ?int $schoolId): void
    {
        if ($schoolId === null) {
            return;
        }

        $schoolColumn = $this->detectSchoolColumn($table);

        if ($schoolColumn !== null) {
            $includeUnassigned = config('services.ai.include_unassigned_records', false);

            if ($includeUnassigned) {
                $queryBuilder->where(function ($q) use ($schoolColumn, $schoolId) {
                    $q->where($schoolColumn, $schoolId)->orWhereNull($schoolColumn);
                });
            } else {
                $queryBuilder->where($schoolColumn, $schoolId);
            }

            return;
        }

        // For tables without a direct school_id, try to join through students/teachers
        // when the table has a student_id or teacher_id foreign key.
        if (Schema::hasColumn($table, 'student_id')) {
            $queryBuilder->whereExists(function ($q) use ($table, $schoolId) {
                $q->select(DB::raw(1))
                    ->from('students')
                    ->whereColumn('students.id', "{$table}.student_id")
                    ->where('students.school_id', $schoolId);
            });
        } elseif (Schema::hasColumn($table, 'teacher_id')) {
            $queryBuilder->whereExists(function ($q) use ($table, $schoolId) {
                $q->select(DB::raw(1))
                    ->from('teachers')
                    ->whereColumn('teachers.id', "{$table}.teacher_id")
                    ->where('teachers.school_id', $schoolId);
            });
        }
    }

    protected function detectSchoolColumn(string $table): ?string
    {
        if (Schema::hasColumn($table, 'school_id')) {
            return 'school_id';
        }

        return null;
    }

    protected function applyFilters($queryBuilder, array $filters, string $table): void
    {
        foreach ($filters as $filter) {
            if (! is_array($filter)) {
                continue;
            }

            $column = $filter['column'] ?? null;
            $operator = $filter['operator'] ?? '=';
            $value = $filter['value'] ?? null;

            if (! is_string($column) || ! $this->schema->isColumnAllowed($table, $column)) {
                throw new AiServiceException("Filter column '{$column}' is not allowed on table '{$table}'.");
            }

            $operator = strtolower((string) $operator);
            $allowedOperators = ['=', '!=', '<>', '<', '>', '<=', '>=', 'like', 'not like', 'in', 'not in', 'between', 'is null', 'is not null'];

            if (! in_array($operator, $allowedOperators, true)) {
                throw new AiServiceException("Operator '{$operator}' is not allowed.");
            }

            match ($operator) {
                'is null' => $queryBuilder->whereNull($column),
                'is not null' => $queryBuilder->whereNotNull($column),
                'in' => $queryBuilder->whereIn($column, (array) $value),
                'not in' => $queryBuilder->whereNotIn($column, (array) $value),
                'between' => $queryBuilder->whereBetween($column, (array) $value),
                default => $queryBuilder->where($column, $operator, $value),
            };
        }
    }

    protected function applyAggregates($queryBuilder, array $aggregates, string $table, array &$columns): void
    {
        if ($aggregates === []) {
            return;
        }

        $allowedFunctions = ['count', 'sum', 'avg', 'min', 'max'];
        $selects = [];

        foreach ($aggregates as $agg) {
            if (! is_array($agg)) {
                continue;
            }

            $column = $agg['column'] ?? '*';
            $function = strtolower($agg['function'] ?? 'count');
            $defaultAlias = preg_replace('/[^a-zA-Z0-9_]/', '_', "{$function}_{$column}");
            $alias = preg_replace('/[^a-zA-Z0-9_]/', '_', (string) ($agg['alias'] ?? $defaultAlias));

            if (! in_array($function, $allowedFunctions, true)) {
                throw new AiServiceException("Aggregate function '{$function}' is not allowed.");
            }

            if ($column !== '*' && ! $this->schema->isColumnAllowed($table, $column)) {
                throw new AiServiceException("Aggregate column '{$column}' is not allowed on table '{$table}'.");
            }

            $selects[] = DB::raw("{$function}({$column}) as {$alias}");
        }

        if ($columns === ['*']) {
            $columns = $selects;
        } else {
            // When specific columns are selected alongside aggregates but no group_by is
            // provided, automatically group by those columns so MySQL/Postgres do not throw
            // a "mixing of GROUP columns" error.
            foreach ($columns as $column) {
                if (is_string($column) && ! str_contains($column, '(')) {
                    $queryBuilder->groupBy($column);
                }
            }

            $columns = array_merge($columns, $selects);
        }

        $queryBuilder->select($columns);
    }

    protected function applyGroupBy($queryBuilder, array $groupBy, string $table): void
    {
        foreach ($groupBy as $column) {
            if (! is_string($column) || ! $this->schema->isColumnAllowed($table, $column)) {
                throw new AiServiceException("Group by column '{$column}' is not allowed on table '{$table}'.");
            }

            $queryBuilder->groupBy($column);
        }
    }

    protected function applyOrderBy($queryBuilder, array $orderBy, string $table): void
    {
        foreach ($orderBy as $order) {
            if (! is_array($order)) {
                continue;
            }

            $column = $order['column'] ?? null;
            $direction = strtolower($order['direction'] ?? 'asc');

            if (! is_string($column) || ! $this->schema->isColumnAllowed($table, $column)) {
                throw new AiServiceException("Order by column '{$column}' is not allowed on table '{$table}'.");
            }

            if (! in_array($direction, ['asc', 'desc'], true)) {
                $direction = 'asc';
            }

            $queryBuilder->orderBy($column, $direction);
        }
    }

    protected function applyLimit($queryBuilder, ?int $limit): void
    {
        $limit = (int) ($limit ?? 100);

        if ($limit <= 0) {
            $limit = 100;
        }

        $queryBuilder->limit(min($limit, 500));
    }
}
