<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Backfill the newly introduced `work_hours.manage` permission.
 *
 * Roles (and permission templates) created before the key existed obviously
 * cannot contain it, which would silently hide the "Teacher Work Hours"
 * screen from admins who manage salaries or teachers. Grant it to any role
 * that already holds `salaries.manage` or `teachers.manage`.
 */
return new class extends Migration
{
    private const NEW_KEY = 'work_hours.manage';

    private const GRANT_IF_ANY = ['salaries.manage', 'teachers.manage'];

    public function up(): void
    {
        foreach (['roles', 'permission_templates'] as $table) {
            if (! DB::getSchemaBuilder()->hasTable($table)) {
                continue;
            }

            DB::table($table)->orderBy('id')->chunk(100, function ($rows) use ($table) {
                foreach ($rows as $row) {
                    $perms = json_decode($row->permissions ?? '[]', true);
                    if (! is_array($perms)) {
                        continue;
                    }

                    $hasWildcard = in_array('*', $perms, true)
                        || (isset($perms['*']) && $perms['*']);
                    $shouldGrant = $hasWildcard;

                    if (! $shouldGrant) {
                        foreach (self::GRANT_IF_ANY as $key) {
                            if (in_array($key, $perms, true) || ! empty($perms[$key])) {
                                $shouldGrant = true;
                                break;
                            }
                        }
                    }

                    if ($shouldGrant && ! in_array(self::NEW_KEY, $perms, true) && empty($perms[self::NEW_KEY])) {
                        // Keep the assoc {key: true} shape — Role::getActivePermissions()
                        // filters by `$v === true`, so a plain list item would be dropped.
                        $perms[self::NEW_KEY] = true;
                        DB::table($table)->where('id', $row->id)->update([
                            'permissions' => json_encode($perms),
                        ]);
                    }
                }
            });
        }
    }

    public function down(): void
    {
        // Intentionally left in place: revoking a permission on rollback
        // could lock legitimate users out of a screen they already use.
    }
};
