<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\My_Parent;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    /**
     * Get the currently authenticated user's profile.
     */
    public function show(Request $request)
    {
        $user = $request->user() ?: auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        return response()->json([
            'success' => true,
            'message' => 'Profile retrieved successfully',
            'data' => $this->normalizeProfile($user)
        ], 200);
    }

    /**
     * Update the currently authenticated user's profile.
     */
    public function update(Request $request)
    {
        $user = $request->user() ?: auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        $class = get_class($user);
        $table = $user->getTable();

        $validator = Validator::make($request->all(), [
            'email' => [
                'nullable',
                'email',
                Rule::unique($table)->ignore($user->id),
            ],
            'password' => 'nullable|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $updates = [];

        if ($request->filled('email')) {
            $updates['email'] = $request->email;
        }

        if ($request->filled('password')) {
            $updates['password'] = Hash::make($request->password);
        }

        if (strpos($class, 'Teacher') !== false) {
            $updates = array_merge($updates, $this->teacherUpdates($request));
        } elseif (strpos($class, 'Student') !== false) {
            $updates = array_merge($updates, $this->studentUpdates($request));
        } elseif (strpos($class, 'My_Parent') !== false || strpos($class, 'Parent') !== false) {
            $updates = array_merge($updates, $this->parentUpdates($request));
        }

        if (!empty($updates)) {
            $user->update($updates);
        }

        // Refresh the model to get updated data
        $user->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => $this->normalizeProfile($user)
        ], 200);
    }

    /**
     * Build update payload for a Teacher.
     */
    private function teacherUpdates(Request $request): array
    {
        $updates = [];

        if ($request->hasAny(['name', 'name_ar', 'name_en'])) {
            $updates['Name'] = $this->localizedValue($request->input('name'), $request->input('name_ar'), $request->input('name_en'));
        }

        if ($request->filled('address')) {
            $updates['Address'] = $request->address;
        }

        return $updates;
    }

    /**
     * Build update payload for a Student.
     */
    private function studentUpdates(Request $request): array
    {
        $updates = [];

        if ($request->hasAny(['name', 'name_ar', 'name_en'])) {
            $updates['name'] = $this->localizedValue($request->input('name'), $request->input('name_ar'), $request->input('name_en'));
        }

        return $updates;
    }

    /**
     * Build update payload for a Parent.
     */
    private function parentUpdates(Request $request): array
    {
        $updates = [];

        if (
            $request->hasAny(['name_father', 'name', 'name_father_ar', 'name_father_en', 'name_ar', 'name_en'])
        ) {
            $updates['Name_Father'] = $this->localizedValue(
                $request->input('name_father') ?? $request->input('name'),
                $request->input('name_father_ar') ?? $request->input('name_ar'),
                $request->input('name_father_en') ?? $request->input('name_en')
            );
        }

        if ($request->hasAny(['name_mother', 'name_mother_ar', 'name_mother_en'])) {
            $updates['Name_Mother'] = $this->localizedValue(
                $request->input('name_mother'),
                $request->input('name_mother_ar'),
                $request->input('name_mother_en')
            );
        }

        if ($request->filled('phone_father')) {
            $updates['Phone_Father'] = $request->phone_father;
        }

        if ($request->filled('phone_mother')) {
            $updates['Phone_Mother'] = $request->phone_mother;
        }

        if ($request->filled('address_father')) {
            $updates['Address_Father'] = $request->address_father;
        }

        if ($request->filled('address_mother')) {
            $updates['Address_Mother'] = $request->address_mother;
        }

        return $updates;
    }

    /**
     * Convert a name value into a translations array.
     */
    private function localizedValue($name, $nameAr = null, $nameEn = null): array
    {
        if (is_array($name)) {
            return [
                'ar' => $name['ar'] ?? $name['ar'] ?? '',
                'en' => $name['en'] ?? $name['en'] ?? '',
            ];
        }

        return [
            'ar' => $nameAr ?: ($name ?: ''),
            'en' => $nameEn ?: ($name ?: ''),
        ];
    }

    /**
     * Normalize user profile for the mobile app.
     */
    private function normalizeProfile($user): array
    {
        $class = get_class($user);
        $base = [
            'id' => $user->id,
            'email' => $user->email,
            'type' => $this->userType($class),
        ];

        if (strpos($class, 'Teacher') !== false) {
            return array_merge($base, [
                'name' => $user->getTranslation('Name', 'ar') ?: $user->Name,
                'name_ar' => $user->getTranslation('Name', 'ar') ?: '',
                'name_en' => $user->getTranslation('Name', 'en') ?: '',
                'address' => $user->Address,
            ]);
        }

        if (strpos($class, 'Student') !== false) {
            return array_merge($base, [
                'name' => $user->getTranslation('name', 'ar') ?: $user->name,
                'name_ar' => $user->getTranslation('name', 'ar') ?: '',
                'name_en' => $user->getTranslation('name', 'en') ?: '',
            ]);
        }

        if (strpos($class, 'My_Parent') !== false || strpos($class, 'Parent') !== false) {
            return array_merge($base, [
                'name_father' => $user->getTranslation('Name_Father', 'ar') ?: $user->Name_Father,
                'name_father_ar' => $user->getTranslation('Name_Father', 'ar') ?: '',
                'name_father_en' => $user->getTranslation('Name_Father', 'en') ?: '',
                'name_mother' => $user->getTranslation('Name_Mother', 'ar') ?: ($user->Name_Mother ?? ''),
                'name_mother_ar' => $user->getTranslation('Name_Mother', 'ar') ?: '',
                'name_mother_en' => $user->getTranslation('Name_Mother', 'en') ?: '',
                'phone_father' => $user->Phone_Father,
                'phone_mother' => $user->Phone_Mother,
                'address_father' => $user->Address_Father,
                'address_mother' => $user->Address_Mother,
            ]);
        }

        return $base;
    }

    /**
     * Map model class to user type string.
     */
    private function userType(string $class): string
    {
        if (strpos($class, 'Student') !== false) {
            return 'student';
        }
        if (strpos($class, 'Teacher') !== false) {
            return 'teacher';
        }
        if (strpos($class, 'Parent') !== false) {
            return 'parent';
        }
        return 'admin';
    }
}
