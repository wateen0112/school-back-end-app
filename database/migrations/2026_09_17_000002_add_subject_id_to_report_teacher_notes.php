<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddSubjectIdToReportTeacherNotes extends Migration
{
    /**
     * Backfill subject_id into report.teacher_notes entries so the API can
     * resolve subject names in the current locale instead of returning the
     * language stored at generation time.
     */
    public function up()
    {
        $notesBySubject = $this->subjectLookup();

        $reports = DB::table('student_monthly_reports')
            ->select('id', 'teacher_notes')
            ->get();

        foreach ($reports as $report) {
            $notes = json_decode($report->teacher_notes, true);
            if (! is_array($notes) || $notes === []) {
                continue;
            }

            $changed = false;

            foreach ($notes as &$note) {
                if (! is_array($note)) {
                    continue;
                }
                if (! empty($note['subject_id'])) {
                    continue;
                }
                $subject = isset($note['subject']) ? (string) $note['subject'] : '';
                if ($subject === '' || ! isset($notesBySubject[$subject])) {
                    continue;
                }

                $note['subject_id'] = $notesBySubject[$subject];
                $changed = true;
            }
            unset($note);

            if ($changed) {
                DB::table('student_monthly_reports')->where('id', $report->id)->update([
                    'teacher_notes' => json_encode($notes, JSON_UNESCAPED_UNICODE),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        // Data-preserving migration; no rollback logic required.
    }

    private function subjectLookup(): array
    {
        $map = [];

        $rows = DB::table('subjects')->select('id', 'Name')->get();

        foreach ($rows as $row) {
            $name = $row->Name;
            $map[(string) $name] = (int) $row->id;

            $decoded = json_decode($name, true);
            if (is_array($decoded)) {
                foreach ($decoded as $label) {
                    $map[(string) $label] = (int) $row->id;
                }
            }
        }

        return $map;
    }
}