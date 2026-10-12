<?php

namespace App\Services;

use App\Models\Section;
use App\Models\SectionSubjectTeacher;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Services\Scoping\SchoolContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Single source of truth for "what is this teacher actually assigned to?".
 *
 * The teacher endpoints used to each resolve assignments differently, which let
 * the same teacher see a different set of sections/subjects depending on which
 * screen he opened. Everything now goes through here so the students screen,
 * the rating (evaluation) screen and the paper-exam screens agree.
 *
 * Three sources feed the assignment, in priority order:
 *
 *   1. `section_subject_teacher` rows  — the real model. One row per
 *      (section, subject, teacher) triple and it allows several teachers to
 *      teach the same subject in different sections.
 *   2. `teacher_section` pivot         — legacy "this teacher owns the section".
 *   3. `subjects.teacher_id`           — legacy "this teacher owns the subject",
 *      which grants every section of that subject's classroom.
 *
 * Sources 2 and 3 only widen the *unfiltered* view. Once a caller asks for a
 * specific subject, only source 1 counts: owning a different subject must never
 * expose the students of a subject you do not teach.
 */
class TeacherAssignmentService
{
    /** Sections the teacher may see, optionally narrowed to one subject. */
    public function sectionIds(Teacher $teacher, ?int $subjectId = null): array
    {
        if ($subjectId !== null) {
            return $this->sectionIdsForSubject($teacher, $subjectId);
        }

        return $this->allSectionIds($teacher);
    }

    /**
     * Sections where this teacher teaches this specific subject.
     *
     * Returns an empty array when the subject is not assigned to the teacher,
     * so an unknown or unassigned `subject_id` can never widen the result set.
     */
    public function sectionIdsForSubject(Teacher $teacher, int $subjectId): array
    {
        $pivotSectionIds = $this->pivotQuery($teacher)
            ->where('subject_id', $subjectId)
            ->pluck('section_id')
            ->all();

        if ($pivotSectionIds !== []) {
            return $this->scopedSectionIds($teacher, $pivotSectionIds);
        }

        // Legacy ownership: the subject row itself points at this teacher.
        $ownsSubject = Subject::where('id', $subjectId)
            ->where('teacher_id', $teacher->id)
            ->exists();

        if (! $ownsSubject) {
            return [];
        }

        $classroomIds = Subject::where('id', $subjectId)
            ->whereNotNull('Classroom_id')
            ->pluck('Classroom_id')
            ->all();

        if ($classroomIds === []) {
            return [];
        }

        return $this->scopedSectionIds(
            $teacher,
            Section::whereIn('Class_id', $classroomIds)->pluck('id')->all()
        );
    }

    /** Every section the teacher may see, across all three assignment sources. */
    public function allSectionIds(Teacher $teacher): array
    {
        $ids = $this->pivotQuery($teacher)->pluck('section_id')->all();

        $ids = array_merge($ids, $teacher->Sections()->pluck('sections.id')->all());

        $classroomIds = Subject::where('teacher_id', $teacher->id)
            ->whereNotNull('Classroom_id')
            ->pluck('Classroom_id')
            ->unique()
            ->all();

        if ($classroomIds !== []) {
            $ids = array_merge(
                $ids,
                Section::whereIn('Class_id', $classroomIds)->pluck('id')->all()
            );
        }

        return $this->scopedSectionIds($teacher, $ids);
    }

    /** Subjects the teacher teaches, from the pivot plus legacy ownership. */
    public function subjectIds(Teacher $teacher): array
    {
        $ids = $this->pivotQuery($teacher)->pluck('subject_id')->all();
        $ids = array_merge($ids, Subject::where('teacher_id', $teacher->id)->pluck('id')->all());

        return array_values(array_unique(array_map('intval', $ids)));
    }

    /**
     * Subjects this teacher teaches in one specific section.
     *
     * Unlike the unfiltered {@see subjectIds()} this is always strict: owning a
     * subject elsewhere never implies you teach it in an arbitrary section.
     */
    public function subjectIdsForSection(Teacher $teacher, int $sectionId): array
    {
        if (! in_array($sectionId, $this->allSectionIds($teacher), true)) {
            return [];
        }

        $pivotSubjectIds = $this->pivotQuery($teacher)
            ->where('section_id', $sectionId)
            ->pluck('subject_id')
            ->all();

        if ($pivotSubjectIds !== []) {
            return array_values(array_unique(array_map('intval', $pivotSubjectIds)));
        }

        // Legacy ownership: subjects pointing at this teacher that belong to the
        // section's classroom.
        $section = Section::find($sectionId);

        if (! $section) {
            return [];
        }

        return Subject::where('teacher_id', $teacher->id)
            ->where('Classroom_id', $section->Class_id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function teachesSubject(Teacher $teacher, int $subjectId): bool
    {
        if (in_array($subjectId, $this->subjectIds($teacher), true)) {
            return true;
        }

        return $this->sectionIdsForSubject($teacher, $subjectId) !== [];
    }

    /** True when the teacher may act on this section (optionally for a subject). */
    public function teachesSection(Teacher $teacher, int $sectionId, ?int $subjectId = null): bool
    {
        if ($subjectId !== null) {
            return in_array($sectionId, $this->sectionIdsForSubject($teacher, $subjectId), true);
        }

        return in_array($sectionId, $this->allSectionIds($teacher), true);
    }

    /**
     * Section/subject pairs the teacher is assigned to, with the nested grade,
     * classroom and subject loaded so the client can build cascading filters
     * without ever producing an invalid combination.
     *
     * @return Collection<int, SectionSubjectTeacher>
     */
    public function assignments(Teacher $teacher): Collection
    {
        $rows = $this->pivotQuery($teacher)->get();

        if ($rows->isEmpty()) {
            return $rows;
        }

        $sectionIds = $rows->pluck('section_id')->unique()->all();
        $subjectIds = $rows->pluck('subject_id')->unique()->all();

        $sections = Section::with(['Grades', 'My_classs'])
            ->whereIn('id', $this->scopedSectionIds($teacher, $sectionIds))
            ->get()
            ->keyBy('id');

        $subjects = Subject::whereIn('id', $subjectIds)->get()->keyBy('id');

        return $rows
            ->filter(fn (SectionSubjectTeacher $row) => $sections->has($row->section_id)
                && $subjects->has($row->subject_id))
            ->map(function (SectionSubjectTeacher $row) use ($sections, $subjects) {
                $row->setRelation('section', $sections->get($row->section_id));
                $row->setRelation('subject', $subjects->get($row->subject_id));

                return $row;
            })
            ->values();
    }

    public function schoolId(Teacher $teacher): ?int
    {
        return SchoolContext::schoolIdFrom($teacher);
    }

    /**
     * Apply the teacher's tenant scope to a query on a school-scoped table.
     */
    public function scopeStudents(Teacher $teacher, \Illuminate\Database\Eloquent\Builder $query)
    {
        SchoolContext::scope($query, $this->schoolId($teacher));

        return $query;
    }

    /**
     * Assignments are only meaningful inside the teacher's own school, so a
     * cross-tenant id in a stale pivot row is dropped here rather than trusted.
     */
    private function scopedSectionIds(Teacher $teacher, array $sectionIds): array
    {
        $sectionIds = array_values(array_unique(array_filter(
            array_map('intval', $sectionIds)
        )));

        if ($sectionIds === []) {
            return [];
        }

        $query = Section::whereIn('id', $sectionIds);
        $schoolId = $this->schoolId($teacher);

        // `sections` carries no school_id of its own. Derive the tenant from the
        // students enrolled in each section and drop any section whose students
        // all belong to another school. Sections with no students yet stay
        // visible, otherwise a brand new section would vanish from the teacher's
        // filters before anyone is enrolled in it.
        if ($schoolId !== null) {
            $foreignSectionIds = Student::whereIn('section_id', $sectionIds)
                ->where(function (Builder $query) use ($schoolId) {
                    $query->whereNull('school_id')->orWhere('school_id', '<>', $schoolId);
                })
                ->distinct()
                ->pluck('section_id')
                ->all();

            if ($foreignSectionIds !== []) {
                $query->whereNotIn('id', $foreignSectionIds);
            }
        }

        return $query->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    private function pivotQuery(Teacher $teacher)
    {
        return SectionSubjectTeacher::where('teacher_id', $teacher->id);
    }
}