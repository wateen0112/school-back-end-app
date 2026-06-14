# Student Promotion API Documentation

API endpoints for managing student promotions.

**Base URL:** `/api/promotion`  
**Authentication:** Required (`auth:api` middleware)

---

## Endpoints

| Method | Endpoint | Action |
|--------|----------|--------|
| `GET` | `/api/promotion` | List all promotions |
| `POST` | `/api/promotion` | Bulk-promote students by current grade/class/section/year |
| `GET` | `/api/promotion/{id}` | Show one promotion record |
| `PUT` / `PATCH` | `/api/promotion/{id}` | Update a single student's promotion |
| `DELETE` | `/api/promotion/{id}` | Revert a specific promotion (or all if `page_id=1`) |

---

## 1. Create / Bulk Promote Students

**`POST /api/promotion`**

Promotes all students matching the current grade, classroom, section, and academic year to the new ones.

### Required Form-Data / JSON Body

| Field | Type | Description |
|-------|------|-------------|
| `Grade_id` | integer | Current grade ID |
| `Classroom_id` | integer | Current classroom ID |
| `section_id` | integer | Current section ID |
| `academic_year` | string | Current academic year |
| `Grade_id_new` | integer | Target/new grade ID |
| `Classroom_id_new` | integer | Target/new classroom ID |
| `section_id_new` | integer | Target/new section ID |
| `academic_year_new` | string | Target/new academic year |

### Example Request (JSON)

```json
{
  "Grade_id": 1,
  "Classroom_id": 2,
  "section_id": 3,
  "academic_year": "2024-2025",
  "Grade_id_new": 2,
  "Classroom_id_new": 4,
  "section_id_new": 5,
  "academic_year_new": "2025-2026"
}
```

### Example Request (Form-Data)

```
Grade_id=1
Classroom_id=2
section_id=3
academic_year=2024-2025
Grade_id_new=2
Classroom_id_new=4
section_id_new=5
academic_year_new=2025-2026
```

### Success Response

```json
{
  "success": true,
  "message": "Students promoted successfully",
  "data": {
    "promoted_count": 25,
    "promotions": [...]
  }
}
```

### Error Response (No students found)

```json
{
  "success": false,
  "message": "No students found for promotion."
}
```

---

## 2. Update a Single Student Promotion

**`PUT /api/promotion/{id}`** or **`PATCH /api/promotion/{id}`**

Updates the target grade/class/section and promotion date for a specific student.

### Required Form-Data / JSON Body

| Field | Type | Description |
|-------|------|-------------|
| `to_grade_id` | integer | New grade ID (must exist in `grades` table) |
| `to_classroom_id` | integer | New classroom ID (must exist in `classrooms` table) |
| `to_section_id` | integer | New section ID (must exist in `sections` table) |
| `promotion_date` | date | Promotion date (`YYYY-MM-DD`) |

### Example Request

```json
{
  "to_grade_id": 3,
  "to_classroom_id": 6,
  "to_section_id": 8,
  "promotion_date": "2025-06-15"
}
```

### Success Response

```json
{
  "success": true,
  "message": "Student promotion updated successfully",
  "data": {
    "id": 1,
    "grade_id": 3,
    "classroom_id": 6,
    "section_id": 8,
    "promotion_date": "2025-06-15",
    "grade": {...},
    "classroom": {...},
    "section": {...},
    "parent": {...}
  }
}
```

---

## 3. Delete / Revert Promotion

**`DELETE /api/promotion/{id}`**

Reverts a promotion and restores the student's previous grade/class/section/academic year.

### Optional Body Parameter

| Field | Type | Description |
|-------|------|-------------|
| `page_id` | integer | Send `page_id=1` to **revert all promotions** and truncate the promotion table. |

### Revert Specific Promotion

```http
DELETE /api/promotion/5
```

### Revert All Promotions

```http
DELETE /api/promotion/1
Content-Type: application/json

{
  "page_id": 1
}
```

### Success Response (Specific)

```json
{
  "success": true,
  "message": "Student promotion reverted successfully"
}
```

### Success Response (All)

```json
{
  "success": true,
  "message": "All student promotions reverted successfully"
}
```

---

## 4. List All Promotions

**`GET /api/promotion`**

Returns all promotion records with related student, grade, classroom, and section data.

### Success Response

```json
{
  "success": true,
  "message": "Student promotions retrieved successfully",
  "data": [
    {
      "id": 1,
      "student_id": 10,
      "from_grade": 1,
      "from_Classroom": 2,
      "from_section": 3,
      "to_grade": 2,
      "to_Classroom": 4,
      "to_section": 5,
      "academic_year": "2024-2025",
      "academic_year_new": "2025-2026",
      "student": {...},
      "f_grade": {...},
      "f_classroom": {...},
      "f_section": {...},
      "t_grade": {...},
      "t_classroom": {...},
      "t_section": {...}
    }
  ]
}
```

---

## 5. Show Single Promotion

**`GET /api/promotion/{id}`**

Returns a single promotion record with related data.

### Success Response

```json
{
  "success": true,
  "message": "Promoted student retrieved successfully",
  "data": {
    "id": 1,
    "student_id": 10,
    "from_grade": 1,
    "from_Classroom": 2,
    "from_section": 3,
    "to_grade": 2,
    "to_Classroom": 4,
    "to_section": 5,
    "academic_year": "2024-2025",
    "academic_year_new": "2025-2026",
    "student": {...},
    "f_grade": {...},
    "f_classroom": {...},
    "f_section": {...},
    "t_grade": {...},
    "t_classroom": {...},
    "t_section": {...}
  }
}
```

---

## Notes

- All routes are protected by the `auth:api` middleware. Include a valid bearer token in the `Authorization` header.
- The `getStudentsForPromotion()` method exists in the controller but is **not currently wired to a route** in `routes/api.php`.
