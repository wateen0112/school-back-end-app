<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Teacher;
use App\Services\AuditLogger;
use App\Services\Roles\PermissionRegistry;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RoleController extends Controller
{
    /**
     * Roles that belong to the request admin's school.
     * (Super admins may target any school via the school_id param.)
     */
    public function index(Request $request)
    {
        $schoolId = $this->resolveSchoolId($request);
        $query = Role::withCount('users')->orderBy('id', 'desc');

        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        $roles = $query->get()->map(fn ($role) => $this->format($role));

        return response()->json(['success' => true, 'message' => 'Roles retrieved successfully', 'data' => $roles], 200);
    }

    public function store(Request $request)
    {
        $schoolId = $this->requireSchoolId($request);
        if ($schoolId instanceof \Illuminate\Http\JsonResponse) {
            return $schoolId;
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation errors', 'errors' => $validator->errors()], 422);
        }

        $role = Role::create([
            'name' => ['ar' => $request->name, 'en' => $request->name_en ?: $request->name],
            'description' => $request->description,
            'permissions' => $this->normalizePermissions($request->permissions ?? []),
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
            'school_id' => $schoolId,
        ]);

        AuditLogger::log('role.created', 'Role', $role->id, 'Ø¥Ù†Ø´Ø§Ø¡ Ø¯ÙˆØ± Ø¬Ø¯ÙŠØ¯ Ù…Ø¹ ØµÙ„Ø§Ø­ÙŠØ§ØªÙ‡');

        return response()->json(['success' => true, 'message' => 'Role created successfully', 'data' => $this->format($role)], 201);
    }

    public function update(Request $request, $id)
    {
        $role = Role::find($id);

        if (! $role) {
            return response()->json(['success' => false, 'message' => 'Role not found'], 404);
        }

        $schoolId = $this->currentSchoolId($request);
        if ($schoolId && $role->school_id !== $schoolId) {
            return response()->json(['success' => false, 'message' => 'Role does not belong to this school'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation errors', 'errors' => $validator->errors()], 422);
        }

        if ($request->filled('name') || $request->filled('name_en')) {
            $translations = $role->getTranslations('name');
            $translations['ar'] = $request->input('name', $translations['ar'] ?? '');
            $translations['en'] = $request->input('name_en', $translations['en'] ?? $translations['ar'] ?? '');
            $role->name = $translations;
        }

        if ($request->has('permissions')) {
            $role->permissions = $this->normalizePermissions($request->permissions);
        }

        if ($request->has('description')) {
            $role->description = $request->input('description');
        }

        if ($request->has('is_active')) {
            $role->is_active = $request->boolean('is_active');
        }

        $role->save();

        AuditLogger::log('role.updated', 'Role', $role->id, 'ØªØ¹Ø¯ÙŠÙ„ Ø¯ÙˆØ± ÙˆØµÙ„Ø§Ø­ÙŠØ§ØªÙ‡');

        return response()->json(['success' => true, 'message' => 'Role updated successfully', 'data' => $this->format($role)], 200);
    }

    public function destroy(Request $request, $id)
    {
        $role = Role::find($id);

        if (! $role) {
            return response()->json(['success' => false, 'message' => 'Role not found'], 404);
        }

        $schoolId = $this->currentSchoolId($request);
        if ($schoolId && $role->school_id !== $schoolId) {
            return response()->json(['success' => false, 'message' => 'Role does not belong to this school'], 403);
        }

        $role->delete();

        AuditLogger::log('role.deleted', 'Role', $id, 'Ø­Ø°Ù Ø¯ÙˆØ±');

        return response()->json(['success' => true, 'message' => 'Role deleted successfully'], 200);
    }

    /**
     * The full permission catalogue (grouped per dashboard section).
     */
    public function permissions()
    {
        return response()->json([
            'success' => true,
            'message' => 'Permissions retrieved successfully',
            'data' => [
                'groups' => PermissionRegistry::grouped(),
                'all' => PermissionRegistry::all(),
            ],
        ], 200);
    }

    /**
     * School staff (users + teachers) with the roles assigned to each.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function users(Request $request)
    {
        $schoolId = $this->resolveSchoolId($request);

        if (! $schoolId) {
            return response()->json(['success' => false, 'message' => 'A school_id is required'], 400);
        }

        $admins = User::where('school_id', $schoolId)
            ->with(['roles' => fn ($q) => $q->select('roles.id', 'roles.name', 'roles.is_active')])
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'is_super_admin'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'type' => 'user',
                'is_super_admin' => $user->is_super_admin,
                'roles' => $user->roles->map(fn (Role $r) => $this->formatRoleLite($r))->values(),
                'permissions' => $user->effectivePermissions(),
            ]);

        $teachers = Teacher::where('school_id', $schoolId)
            ->with(['roles' => fn ($q) => $q->select('roles.id', 'roles.name', 'roles.is_active')])
            ->orderBy('Name')
            ->get()
            ->map(fn (Teacher $teacher) => [
                'id' => $teacher->id,
                'name' => $teacher->Name,
                'email' => $teacher->email,
                'type' => 'teacher',
                'is_super_admin' => false,
                'roles' => $teacher->roles->map(fn (Role $r) => $this->formatRoleLite($r))->values(),
                'permissions' => $teacher->effectivePermissions(),
            ]);

        $users = $admins->concat($teachers)->sortBy('name')->values();

        return response()->json(['success' => true, 'message' => 'School users retrieved successfully', 'data' => $users], 200);
    }

    /**
     * Assign a set of roles to a school staff member (user or teacher),
     * replacing the previous set. A member can hold multiple roles - permissions are merged.
     */
    public function assignRoles(Request $request, $id)
    {
        $schoolId = $this->requireSchoolId($request);
        if ($schoolId instanceof \Illuminate\Http\JsonResponse) {
            return $schoolId;
        }

        $type = $request->input('type', 'user');
        $subject = $this->findSubject($schoolId, $type, $id);

        if (! $subject) {
            return response()->json(['success' => false, 'message' => 'User not found in this school'], 404);
        }

        $validator = Validator::make($request->all(), [
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'integer|exists:roles,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation errors', 'errors' => $validator->errors()], 422);
        }

        $roleIds = array_unique(array_map('intval', $request->role_ids ?? []));

        // Only roles that belong to the same school can be assigned.
        $validRoleIds = Role::where('school_id', $schoolId)
            ->whereIn('id', $roleIds)
            ->where('is_active', true)
            ->pluck('id')
            ->all();

        $subject->roles()->sync($validRoleIds);

        AuditLogger::log($type === 'teacher' ? 'role.assigned.teacher' : 'role.assigned', ucfirst($type), $subject->id, 'ØªØ­Ø¯ÙŠØ« Ø£Ø¯ÙˆØ§Ø± Ø¹Ø¶Ùˆ Ø§Ù„Ù…Ø¯Ø±Ø³Ø©', [
            'role_ids' => $validRoleIds,
        ]);

        $subject->load(['roles' => fn ($q) => $q->select('roles.id', 'roles.name', 'roles.is_active')]);

        return response()->json([
            'success' => true,
            'message' => 'Roles assigned successfully',
            'data' => [
                'id' => $subject->id,
                'name' => $subject instanceof Teacher ? $subject->Name : $subject->name,
                'email' => $subject->email,
                'type' => $type,
                'is_super_admin' => $subject instanceof Teacher ? false : $subject->is_super_admin,
                'roles' => $subject->roles->map(fn (Role $r) => $this->formatRoleLite($r))->values(),
                'permissions' => $subject->effectivePermissions(),
            ],
        ], 200);
    }

    /**
     * Create a new school admin user account.
     */
    public function storeUser(Request $request)
    {
        $schoolId = $this->requireSchoolId($request);
        if ($schoolId instanceof \Illuminate\Http\JsonResponse) {
            return $schoolId;
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'integer|exists:roles,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation errors', 'errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => \Illuminate\Support\Facades\Hash::make($request->password),
            'school_id' => $schoolId,
            'is_super_admin' => false,
        ]);

        if ($request->filled('role_ids')) {
            $roleIds = array_unique(array_map('intval', $request->role_ids));
            $validRoleIds = Role::where('school_id', $schoolId)
                ->whereIn('id', $roleIds)
                ->where('is_active', true)
                ->pluck('id')
                ->all();
            $user->roles()->sync($validRoleIds);
        }

        AuditLogger::log('user.created', 'User', $user->id, 'Ø¥Ù†Ø´Ø§Ø¡ Ø­Ø³Ø§Ø¨ Ù…Ø³ØªØ®Ø¯Ù… Ù…Ø¯Ø±Ø³ÙŠ Ø¬Ø¯ÙŠØ¯');

        $user->load(['roles' => fn ($q) => $q->select('roles.id', 'roles.name', 'roles.is_active')]);

        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'type' => 'user',
                'is_super_admin' => $user->is_super_admin,
                'roles' => $user->roles->map(fn (Role $r) => $this->formatRoleLite($r))->values(),
                'permissions' => $user->effectivePermissions(),
            ],
        ], 201);
    }

    /* ---------------------------------------------------------------- */

    private function currentSchoolId(Request $request): ?int
    {
        $user = $request->user();

        if ($user && $user->school_id) {
            return (int) $user->school_id;
        }

        return $request->filled('school_id') && $request->user()->is_super_admin
            ? (int) $request->input('school_id')
            : null;
    }

    private function resolveSchoolId(Request $request): ?int
    {
        return $this->currentSchoolId($request);
    }

    private function requireSchoolId(Request $request)
    {
        $schoolId = $this->currentSchoolId($request);

        if (! $schoolId) {
            return response()->json(['success' => false, 'message' => 'A school_id is required for this operation'], 400);
        }

        return $schoolId;
    }

    private function findSubject(int $schoolId, string $type, int $id)
    {
        if ($type === 'teacher') {
            return Teacher::where('school_id', $schoolId)->find($id);
        }

        return User::where('school_id', $schoolId)->find($id);
    }

    private function normalizePermissions(array $permissions): array
    {
        $known = collect(PermissionRegistry::all())->pluck('key')->all();
        $out = [];

        foreach ($permissions as $key => $value) {
            if ($key === '*') {
                $out[$key] = (bool) $value;
                continue;
            }
            if (is_string($key) && in_array($key, $known, true)) {
                $out[$key] = (bool) $value;
            }
        }

        return $out;
    }

    private function format(Role $role): array
    {
        $data = $role->toArray();
        $data['name'] = $role->getTranslations('name');
        $data['users_count'] = $role->users_count ?? $role->users()->count();

        return $data;
    }

    private function formatRoleLite(Role $role): array
    {
        return [
            'id' => $role->id,
            'name' => $role->getTranslations('name'),
            'is_active' => $role->is_active,
            'permissions' => $role->permissions ?? [],
        ];
    }
}
