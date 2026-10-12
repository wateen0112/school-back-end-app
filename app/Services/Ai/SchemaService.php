<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SchemaService
{
    /**
     * Tables the AI assistant is allowed to query.
     */
    protected array $allowedTables = [
        'students',
        'teachers',
        'Classrooms',
        'grades',
        'sections',
        'attendances',
        'paper_exams',
        'paper_exam_marks',
        'fee_invoices',
        'student_installments',
        'payments',
        'receipt_students',
        'processing_fees',
        'student_accounts',
        'events',
        'announcements',
        'subscriptions',
        'plans',
        'schools',
    ];

    /**
     * Build a compact schema description for the system prompt.
     */
    public function describeAllowedTables(): string
    {
        $lines = [];

        foreach ($this->allowedTables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $columns = Schema::getColumns($table);
            $columnDefs = [];

            foreach ($columns as $col) {
                $type = $col['type_name'] ?? $col['type'] ?? 'unknown';
                $columnDefs[] = "{$col['name']}: {$type}";
            }

            $lines[] = "- {$table}: " . implode(', ', $columnDefs);
        }

        return implode("\n", $lines);
    }

    /**
     * Get the list of allowed table names.
     */
    public function allowedTables(): array
    {
        return array_values(array_filter(
            $this->allowedTables,
            fn (string $table) => Schema::hasTable($table)
        ));
    }

    /**
     * Verify a table name is allowed.
     */
    public function isTableAllowed(string $table): bool
    {
        return in_array($table, $this->allowedTables, true) && Schema::hasTable($table);
    }

    /**
     * Verify a column exists on an allowed table.
     */
    public function isColumnAllowed(string $table, string $column): bool
    {
        if (! $this->isTableAllowed($table)) {
            return false;
        }

        return Schema::hasColumn($table, $column);
    }
}
