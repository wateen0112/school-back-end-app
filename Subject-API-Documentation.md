# Subject API Documentation

## Create New Subject API

### Endpoint
```
POST /api/subjects
```

### Authentication
Required: `auth:api` middleware

### Headers
```
Content-Type: application/json
Authorization: Bearer {token}
```

### Request Body
```json
{
    "Name": "Mathematics",
    "Grade_id": 1,
    "Classroom_id": 1,
    "teacher_id": 1
}
```

### Required Fields
- **Name** (string, max 255): Subject name
- **Grade_id** (integer): Must exist in grades table
- **Classroom_id** (integer): Must exist in classrooms table  
- **teacher_id** (integer): Must exist in teachers table

### Success Response (201 Created)
```json
{
    "success": true,
    "message": "Subject created successfully",
    "data": {
        "id": 1,
        "Name": {
            "en": "Mathematics",
            "ar": "Mathematics"
        },
        "Grade_id": 1,
        "Classroom_id": 1,
        "teacher_id": 1,
        "created_at": "2023-01-01T00:00:00.000000Z",
        "updated_at": "2023-01-01T00:00:00.000000Z",
        "grade": {
            "id": 1,
            "Name": "Grade 1"
        },
        "teacher": {
            "id": 1,
            "Name": "John Doe",
            "email": "john@example.com"
        }
    }
}
```

### Validation Error Response (422 Unprocessable Entity)
```json
{
    "success": false,
    "message": "Validation errors",
    "errors": {
        "Name": ["The Name field is required."],
        "Grade_id": ["The Grade id field is required."],
        "Classroom_id": ["The Classroom id field is required."],
        "teacher_id": ["The teacher id field is required."]
    }
}
```

### Error Response (500 Internal Server Error)
```json
{
    "success": false,
    "message": "Error creating subject",
    "error": "Detailed error message"
}
```

## Required APIs to Fetch Item IDs

### 1. Get Grades
**Endpoint:** `GET /api/public/grades`  
**Authentication:** None required

**Response:**
```json
{
    "success": true,
    "message": "Public grades retrieved successfully",
    "data": [
        {
            "id": 1,
            "Name": "Grade 1"
        },
        {
            "id": 2,
            "Name": "Grade 2"
        }
    ]
}
```

### 2. Get Classrooms
**Endpoint:** `GET /api/classrooms`  
**Authentication:** Required

**Response:**
```json
{
    "success": true,
    "message": "Classrooms retrieved successfully",
    "data": [
        {
            "id": 1,
            "Name": "Class A",
            "Grade_id": 1
        },
        {
            "id": 2,
            "Name": "Class B",
            "Grade_id": 1
        }
    ]
}
```

### 3. Get Teachers
**Endpoint:** `GET /api/teachers`  
**Authentication:** Required

**Response:**
```json
{
    "success": true,
    "message": "Teachers retrieved successfully",
    "data": [
        {
            "id": 1,
            "Name": "John Doe",
            "email": "john@example.com"
        },
        {
            "id": 2,
            "Name": "Jane Smith",
            "email": "jane@example.com"
        }
    ]
}
```

## Additional Subject APIs

### List All Subjects
**Endpoint:** `GET /api/subjects`  
**Authentication:** Required

### Get Specific Subject
**Endpoint:** `GET /api/subjects/{id}`  
**Authentication:** Required

### Update Subject
**Endpoint:** `PUT /api/subjects/{id}`  
**Authentication:** Required

### Delete Subject
**Endpoint:** `DELETE /api/subjects/{id}`  
**Authentication:** Required

## Implementation Notes

- The SubjectController automatically handles bilingual storage (English and Arabic) for the subject name
- All foreign key relationships are validated during creation/update
- The API uses standard Laravel API Resource patterns
- Error responses follow consistent JSON structure across all endpoints
