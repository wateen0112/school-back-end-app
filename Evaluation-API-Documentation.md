# Evaluation API Documentation

## Overview

The Evaluation API allows teachers to evaluate students monthly for each subject. Evaluations are based on levels: **bad**, **good**, **very_good**, **excellent**, with optional notes.

---

## Evaluation Levels

| Level | Value | Description |
|-------|-------|-------------|
| `bad` | 1 | Needs improvement |
| `good` | 2 | Acceptable performance |
| `very_good` | 3 | Good performance |
| `excellent` | 4 | Outstanding performance |

---

## Base URL

All evaluation endpoints are prefixed with `/api/`

---

## Authentication

- **Read endpoints**: Require any authenticated user (admin, teacher, student, parent) via Sanctum token
- **Write endpoints**: Require any authenticated user; teachers may only evaluate students in their assigned sections/subjects
- **Teacher-scoped endpoints** (`/api/teacher/*`): Require an authenticated teacher
- **Parent reports**: Parent can only see their own children's evaluations

Include the token in the header:
```
Authorization: Bearer {your_token}
```

---

## Endpoints

### 1. List All Evaluations (GET)

```
GET /api/evaluations
```

**Query Parameters (optional filters):**
| Parameter | Type | Description |
|-----------|------|-------------|
| `student_id` | integer | Filter by student |
| `teacher_id` | integer | Filter by teacher |
| `subject_id` | integer | Filter by subject |
| `grade_id` | integer | Filter by grade |
| `classroom_id` | integer | Filter by classroom |
| `section_id` | integer | Filter by section |
| `month` | integer (1-12) | Filter by month |
| `year` | integer | Filter by year |
| `evaluation_level` | string | Filter by level: bad, good, very_good, excellent |

**Response:**
```json
{
  "success": true,
  "message": "Evaluations retrieved successfully",
  "data": [
    {
      "id": 1,
      "student_id": 5,
      "teacher_id": 2,
      "subject_id": 3,
      "grade_id": 1,
      "classroom_id": 2,
      "section_id": 1,
      "evaluation_level": "excellent",
      "notes": "Great participation in class",
      "month": 6,
      "year": 2026,
      "created_at": "2026-06-27T12:00:00.000000Z",
      "updated_at": "2026-06-27T12:00:00.000000Z",
      "student": { ... },
      "teacher": { ... },
      "subject": { ... },
      "grade": { ... },
      "classroom": { ... },
      "section": { ... }
    }
  ]
}
```

---

### 2. Get Single Evaluation (GET)

```
GET /api/evaluations/{id}
```

**Response:**
```json
{
  "success": true,
  "message": "Evaluation retrieved successfully",
  "data": { ...evaluation object... }
}
```

---

### 3. Create Evaluation (POST) - Admin Only

```
POST /api/evaluations
```

**Request Body:**
```json
{
  "student_id": 5,
  "teacher_id": 2,
  "subject_id": 3,
  "grade_id": 1,
  "classroom_id": 2,
  "section_id": 1,
  "evaluation_level": "excellent",
  "notes": "Great participation in class discussions",
  "month": 6,
  "year": 2026
}
```

**Validation Rules:**
- All IDs must exist in their respective tables
- `evaluation_level` must be one of: `bad`, `good`, `very_good`, `excellent`
- `month` must be between 1 and 12
- `year` must be between 2000 and 2100
- Only one evaluation per student per subject per month/year is allowed

**Response:**
```json
{
  "success": true,
  "message": "Evaluation created successfully",
  "data": { ...evaluation with relationships... }
}
```

---

### 4. Bulk Create Evaluations (POST) - Admin Only

```
POST /api/evaluations/bulk
```

**Request Body:**
```json
{
  "evaluations": [
    {
      "student_id": 5,
      "teacher_id": 2,
      "subject_id": 3,
      "grade_id": 1,
      "classroom_id": 2,
      "section_id": 1,
      "evaluation_level": "excellent",
      "notes": "Great work",
      "month": 6,
      "year": 2026
    },
    {
      "student_id": 6,
      "teacher_id": 2,
      "subject_id": 3,
      "grade_id": 1,
      "classroom_id": 2,
      "section_id": 1,
      "evaluation_level": "good",
      "notes": "Good effort",
      "month": 6,
      "year": 2026
    }
  ]
}
```

**Response:**
```json
{
  "success": true,
  "message": "Bulk evaluations processed successfully",
  "data": {
    "created_count": 2,
    "evaluations": [ ... ],
    "errors": []
  }
}
```

---

### 5. Update Evaluation (PUT) - Admin Only

```
PUT /api/evaluations/{id}
```

**Request Body:**
```json
{
  "evaluation_level": "very_good",
  "notes": "Updated notes"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Evaluation updated successfully",
  "data": { ...updated evaluation... }
}
```

---

### 6. Delete Evaluation (DELETE) - Admin Only

```
DELETE /api/evaluations/{id}
```

**Response:**
```json
{
  "success": true,
  "message": "Evaluation deleted successfully"
}
```

---

## Report Endpoints

### 7. Report by Student

```
GET /api/evaluations/report/student/{studentId}
```

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `month` | integer | Filter by month |
| `year` | integer | Filter by year |
| `subject_id` | integer | Filter by subject |

**Response:**
```json
{
  "success": true,
  "message": "Student evaluation report retrieved successfully",
  "data": {
    "student": { ... },
    "evaluations": [ ... ],
    "statistics": {
      "total": 10,
      "bad": 0,
      "good": 2,
      "very_good": 5,
      "excellent": 3,
      "bad_percentage": 0,
      "good_percentage": 20,
      "very_good_percentage": 50,
      "excellent_percentage": 30,
      "average_level": 3.1
    }
  }
}
```

---

### 8. Report by Teacher

```
GET /api/evaluations/report/teacher/{teacherId}
```

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `month` | integer | Filter by month |
| `year` | integer | Filter by year |
| `subject_id` | integer | Filter by subject |

---

### 9. Report by Subject

```
GET /api/evaluations/report/subject/{subjectId}
```

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `month` | integer | Filter by month |
| `year` | integer | Filter by year |
| `teacher_id` | integer | Filter by teacher |

---

### 10. Report by Classroom

```
GET /api/evaluations/report/classroom/{classroomId}
```

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `month` | integer | Filter by month |
| `year` | integer | Filter by year |
| `subject_id` | integer | Filter by subject |
| `teacher_id` | integer | Filter by teacher |

**Response includes `by_student` grouping with average level per student.**

---

### 11. Report by Grade

```
GET /api/evaluations/report/grade/{gradeId}
```

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `month` | integer | Filter by month |
| `year` | integer | Filter by year |
| `subject_id` | integer | Filter by subject |

**Response includes `by_classroom` grouping with average level per classroom.**

---

### 12. Monthly Summary Report

```
GET /api/evaluations/report/monthly-summary?month=6&year=2026
```

**Required Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `month` | integer (1-12) | Required - Month number |
| `year` | integer | Required - Year |

**Optional Filters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `grade_id` | integer | Filter by grade |
| `classroom_id` | integer | Filter by classroom |
| `teacher_id` | integer | Filter by teacher |

**Response includes:**
- All evaluations for the month
- `by_subject` grouping
- `by_grade` grouping
- Overall statistics

---

### 13. Parent Report - My Children's Evaluations

```
GET /api/evaluations/report/parent
```

**Available to:** Parents (returns only their own children's evaluations)

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `month` | integer | Filter by month |
| `year` | integer | Filter by year |
| `student_id` | integer | Filter by specific child |
| `subject_id` | integer | Filter by subject |

**Response:**
```json
{
  "success": true,
  "message": "Parent evaluation report retrieved successfully",
  "data": {
    "children": [ ...list of parent's children... ],
    "evaluations_by_student": {
      "5": {
        "student": { ... },
        "evaluations": [ ... ],
        "statistics": { ... }
      }
    },
    "overall_statistics": { ... }
  }
}
```

---

## Teacher-Scoped Endpoints

These endpoints return data scoped to the authenticated teacher's assigned sections and subjects.

### 14. Get Teacher's Sections

```
GET /api/teacher/sections
```

**Response:**
```json
{
  "success": true,
  "message": "Teacher sections retrieved successfully",
  "data": [
    {
      "id": 1,
      "Name_Section": "Section A",
      "Grade_id": 1,
      "Class_id": 2,
      "Grades": { "id": 1, "Name": "Grade 1" },
      "My_classs": { "id": 2, "Name_Class": "Class 2" }
    }
  ]
}
```

### 15. Get Teacher's Subjects

```
GET /api/teacher/subjects
```

**Response:**
```json
{
  "success": true,
  "message": "Teacher subjects retrieved successfully",
  "data": [
    {
      "id": 1,
      "Name": "Mathematics",
      "Grade_id": 1,
      "Classroom_id": 2,
      "teacher_id": 3
    }
  ]
}
```

### 16. Get Teacher's Students

```
GET /api/teacher/students
```

**Query Parameters (optional filters):**
| Parameter | Type | Description |
|-----------|------|-------------|
| `grade_id` | integer | Filter by grade |
| `classroom_id` | integer | Filter by classroom |
| `section_id` | integer | Filter by section |
| `subject_id` | integer | Filter by subject (narrows to sections in the subject's classroom) |

**Response:**
```json
{
  "success": true,
  "message": "Teacher students retrieved successfully",
  "data": [
    {
      "id": 5,
      "name": "Ahmed Ali",
      "Grade_id": 1,
      "Classroom_id": 2,
      "section_id": 1,
      "grade": { "id": 1, "Name": "Grade 1" },
      "classroom": { "id": 2, "Name_Class": "Class 2" },
      "section": { "id": 1, "Name_Section": "Section A" }
    }
  ]
}
```

---

## Error Responses

All endpoints return consistent error responses:

```json
{
  "success": false,
  "message": "Error description",
  "error": "Detailed error message"
}
```

**Common HTTP Status Codes:**
| Status | Meaning |
|--------|---------|
| 200 | Success |
| 201 | Created |
| 404 | Resource not found |
| 422 | Validation error |
| 500 | Server error |

---

## Example Workflow

### Teacher Evaluates a Class Monthly

1. **Get sections assigned to the teacher:**
   ```
   GET /api/teacher/sections
   ```

2. **Get subjects for the teacher:**
   ```
   GET /api/teacher/subjects
   ```

3. **Get students in the teacher's sections (optionally filtered):**
   ```
   GET /api/teacher/students?section_id=1&subject_id=2
   ```

4. **Create bulk evaluations:**
   ```
   POST /api/evaluations/bulk
   {
     "evaluations": [
       {
         "student_id": 5, "teacher_id": 3, "subject_id": 2,
         "grade_id": 1, "classroom_id": 2, "section_id": 1,
         "evaluation_level": "excellent", "month": 6, "year": 2026
       },
       ...more students...
     ]
   }
   ```

4. **Parent views child's evaluations:**
   ```
   GET /api/evaluations/report/parent?student_id=5&month=6&year=2026
   ```

5. **Admin generates monthly report:**
   ```
   GET /api/evaluations/report/monthly-summary?month=6&year=2026&grade_id=1
   ```
