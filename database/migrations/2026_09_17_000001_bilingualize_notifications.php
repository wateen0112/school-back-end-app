<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class BilingualizeNotifications extends Migration
{
    /**
     * Convert plain-text notification titles/bodies into bilingual
     * JSON {"en": "...", "ar": "..."} so the app can pick the right
     * language per locale. Known English templates are translated here;
     * anything else keeps the original text as both variants.
     */
    public function up()
    {
        $rows = DB::table('notifications')->select('id', 'title', 'body')->get();

        foreach ($rows as $row) {
            if ($this->isJson($row->title) && $this->isJson($row->body)) {
                continue;
            }

            DB::table('notifications')->where('id', $row->id)->update([
                'title' => $this->bilingual('title', $row->title),
                'body'  => $this->bilingual('body', $row->body),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        // Data-preserving migration; no rollback logic required.
    }

    private function isJson($value): bool
    {
        return is_string($value) && str_starts_with(trim($value), '{') && json_decode($value) !== null;
    }

    private function bilingual(string $field, $value): string
    {
        if ($this->isJson($value)) {
            return $value;
        }

        $value = (string) $value;
        $ar = $this->translate($field, $value);

        return json_encode([
            'en' => $value,
            'ar' => $ar ?: $value,
        ], JSON_UNESCAPED_UNICODE);
    }

    private function translate(string $field, string $value): ?string
    {
        if ($field === 'title' && $value === 'New exam available') {
            return 'اختبار جديد متاح';
        }

        if ($field === 'body') {
            if (preg_match("/^A new exam '(.+)' has been added\. Tap to start\.$/", $value, $m)) {
                return "تم إضافة اختبار '{$m[1]}'. اضغط لبدء الاختبار.";
            }
        }

        return null;
    }
}