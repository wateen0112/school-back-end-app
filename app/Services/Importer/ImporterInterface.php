<?php

namespace App\Services\Importer;

use Illuminate\Database\Eloquent\Model;

interface ImporterInterface
{
    public function key(): string;
    public function label(): string;
    public function columns(): array;
    public function validate(array $row, int $schoolId): ?array;
    public function store(array $row, int $schoolId): Model;
}
