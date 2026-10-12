<?php

namespace App\Services\Importer;

use App\Models\Classroom;
use App\Models\Gender;
use App\Models\Grade;
use App\Models\Nationalitie;
use App\Models\Section;
use App\Models\Specialization;
use App\Models\Type_Blood;
use Illuminate\Support\Facades\DB;

class ImportLookups
{
    private const LOCALES = ['en', 'ar'];

    protected static function translatableLookup(string $model, string $column, string $value): ?int
    {
        foreach (self::LOCALES as $locale) {
            $found = $model::query()
                ->where("{$column}->{$locale}", $value)
                ->orWhere("{$column}->{$locale}", mb_strtolower($value))
                ->orWhere("{$column}->{$locale}", mb_strtoupper($value))
                ->first();
            if ($found) return $found->id;
        }

        return null;
    }

    public static function genderName(string $name): ?int
    {
        $name = trim($name);
        if ($name === '') return null;

        return static::translatableLookup(Gender::class, 'Name', $name);
    }

    public static function bloodName(string $name): ?int
    {
        $name = trim($name);
        if ($name === '') return null;

        $found = Type_Blood::where('Name', $name)->first();
        if ($found) return $found->id;

        return Type_Blood::where('Name', mb_strtolower($name))->first()?->id ?? Type_Blood::where('Name', mb_strtoupper($name))->first()?->id;
    }

    public static function nationalityName(string $name): ?int
    {
        $name = trim($name);
        if ($name === '') return null;

        return static::translatableLookup(Nationalitie::class, 'Name', $name);
    }

    public static function gradeName(string $name): ?int
    {
        $name = trim($name);
        if ($name === '') return null;

        return static::translatableLookup(Grade::class, 'Name', $name);
    }

    public static function classroomName(string $name, ?int $gradeId = null): ?int
    {
        $name = trim($name);
        if ($name === '') return null;

        return static::translatableLookupWithExtra(Classroom::class, 'Name_Class', $name, 'Grade_id', $gradeId);
    }

    public static function sectionName(string $name, ?int $classId = null): ?int
    {
        $name = trim($name);
        if ($name === '') return null;

        return static::translatableLookupWithExtra(Section::class, 'Name_Section', $name, 'Class_id', $classId);
    }

    public static function specializationName(string $name): ?int
    {
        $name = trim($name);
        if ($name === '') return null;

        return static::translatableLookup(Specialization::class, 'Name', $name);
    }

    protected static function translatableLookupWithExtra(string $model, string $column, string $value, ?string $extraColumn = null, mixed $extraValue = null): ?int
    {
        foreach (self::LOCALES as $locale) {
            $query = $model::query()
                ->where("{$column}->{$locale}", $value)
                ->orWhere("{$column}->{$locale}", mb_strtolower($value))
                ->orWhere("{$column}->{$locale}", mb_strtoupper($value));
            if ($extraColumn && $extraValue !== null) {
                $query->where($extraColumn, $extraValue);
            }
            $found = $query->first();
            if ($found) return $found->id;
        }

        return null;
    }

    public static function firstDefault(string $model): ?int
    {
        $row = DB::table($model)->select('id')->orderBy('id')->first();

        return $row?->id;
    }

    public static function sectionForClassroom(?int $classId): ?int
    {
        if ($classId !== null) {
            $row = DB::table('sections')->where('Class_id', $classId)->select('id')->orderBy('id')->first();
            if ($row) {
                return (int) $row->id;
            }
        }

        return static::firstDefault('sections');
    }

    public static function firstTeacher(): ?int
    {
        return static::firstDefault('teachers');
    }

    public static function parentEmail(string $email): ?int
    {
        $email = trim($email);
        if ($email === '') return null;

        $row = DB::table('my__parents')->where('Email', $email)->first();
        return $row?->id;
    }

    public static function studentEmail(string $email, int $schoolId): ?int
    {
        $email = trim($email);
        if ($email === '') return null;

        $row = DB::table('students')->where('email', $email)->where('school_id', $schoolId)->first();
        return $row?->id;
    }

    public static function teacherEmail(string $email): ?int
    {
        $email = trim($email);
        if ($email === '') return null;

        $row = DB::table('teachers')->where('Email', $email)->first();
        return $row?->id;
    }
}
