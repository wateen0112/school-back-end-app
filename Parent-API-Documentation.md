# Parent API Documentation

## Create New Parent API

### Endpoint
```
POST /api/parents
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
    "Father_Name": "John Smith",
    "Father_National_ID": "1234567890123",
    "Father_Phone": "+1234567890",
    "Father_Job": "Engineer",
    "Father_Nationality": "American",
    "Father_Blood_Type": "A+",
    "Father_Address": "123 Main St, City",
    "Father_Religion": "Christian",
    "Mother_Name": "Jane Smith",
    "Mother_National_ID": "9876543210987",
    "Mother_Phone": "+1234567891",
    "Mother_Job": "Teacher",
    "Mother_Nationality": "American",
    "Mother_Blood_Type": "B+",
    "Mother_Address": "123 Main St, City",
    "Mother_Religion": "Christian",
    "email": "parent@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

### Required Fields
**Father Information:**
- **Father_Name** (string, max 255): Father's full name
- **Father_National_ID** (string, max 255): Father's national ID (must be unique)
- **Father_Phone** (string, max 255): Father's phone number
- **Father_Job** (string, max 255): Father's occupation
- **Father_Nationality** (string, max 255): Father's nationality
- **Father_Blood_Type** (string, max 255): Father's blood type
- **Father_Address** (string, max 255): Father's address
- **Father_Religion** (string, max 255): Father's religion

**Mother Information:**
- **Mother_Name** (string, max 255): Mother's full name
- **Mother_National_ID** (string, max 255): Mother's national ID (must be unique)
- **Mother_Phone** (string, max 255): Mother's phone number
- **Mother_Job** (string, max 255): Mother's occupation
- **Mother_Nationality** (string, max 255): Mother's nationality
- **Mother_Blood_Type** (string, max 255): Mother's blood type
- **Mother_Address** (string, max 255): Mother's address
- **Mother_Religion** (string, max 255): Mother's religion

**Contact Information:**
- **email** (email): Parent's email address (must be unique)

**Authentication Information:**
- **password** (string, min 6): Parent account password
- **password_confirmation** (string): Must match password field

### Success Response (201 Created)
```json
{
    "success": true,
    "message": "Parent created successfully",
    "data": {
        "id": 1,
        "Father_Name": {
            "en": "John Smith",
            "ar": "John Smith"
        },
        "Father_National_ID": "1234567890123",
        "Father_Phone": "+1234567890",
        "Father_Job": {
            "en": "Engineer",
            "ar": "Engineer"
        },
        "Father_Nationality": {
            "en": "American",
            "ar": "American"
        },
        "Father_Blood_Type": "A+",
        "Father_Address": "123 Main St, City",
        "Father_Religion": "Christian",
        "Mother_Name": {
            "en": "Jane Smith",
            "ar": "Jane Smith"
        },
        "Mother_National_ID": "9876543210987",
        "Mother_Phone": "+1234567891",
        "Mother_Job": {
            "en": "Teacher",
            "ar": "Teacher"
        },
        "Mother_Nationality": {
            "en": "American",
            "ar": "American"
        },
        "Mother_Blood_Type": "B+",
        "Mother_Address": "123 Main St, City",
        "Mother_Religion": "Christian",
        "email": "parent@example.com",
        "created_at": "2023-01-01T00:00:00.000000Z",
        "updated_at": "2023-01-01T00:00:00.000000Z"
    }
}
```

### Validation Error Response (422 Unprocessable Entity)
```json
{
    "success": false,
    "message": "Validation errors",
    "errors": {
        "Father_Name": ["The Father Name field is required."],
        "Father_National_ID": ["The Father National ID field is required.", "The Father National ID has already been taken."],
        "Father_Phone": ["The Father Phone field is required."],
        "Father_Job": ["The Father Job field is required."],
        "Father_Nationality": ["The Father Nationality field is required."],
        "Father_Blood_Type": ["The Father Blood Type field is required."],
        "Father_Address": ["The Father Address field is required."],
        "Father_Religion": ["The Father Religion field is required."],
        "Mother_Name": ["The Mother Name field is required."],
        "Mother_National_ID": ["The Mother National ID field is required.", "The Mother National ID has already been taken."],
        "Mother_Phone": ["The Mother Phone field is required."],
        "Mother_Job": ["The Mother Job field is required."],
        "Mother_Nationality": ["The Mother Nationality field is required."],
        "Mother_Blood_Type": ["The Mother Blood Type field is required."],
        "Mother_Address": ["The Mother Address field is required."],
        "Mother_Religion": ["The Mother Religion field is required."],
        "email": ["The email field is required.", "The email must be a valid email address.", "The email has already been taken."],
        "password": ["The password field is required.", "The password must be at least 6 characters.", "The password confirmation does not match."]
    }
}
```

### Error Response (500 Internal Server Error)
```json
{
    "success": false,
    "message": "Error creating parent",
    "error": "Detailed error message"
}
```

## Required APIs to Fetch Dropdown Values

### 1. Get Nationalities
**Endpoint:** `GET /api/nationalities`  
**Authentication:** Required

**Response:**
```json
{
    "success": true,
    "message": "Nationalities retrieved successfully",
    "data": [
        {
            "id": 1,
            "Name": "American"
        },
        {
            "id": 2,
            "Name": "British"
        },
        {
            "id": 3,
            "Name": "Canadian"
        }
    ]
}
```

### 2. Get Blood Types
**Endpoint:** `GET /api/blood-types`  
**Authentication:** Required

**Response:**
```json
{
    "success": true,
    "message": "Blood types retrieved successfully",
    "data": [
        {
            "id": 1,
            "Name": "A+"
        },
        {
            "id": 2,
            "Name": "A-"
        },
        {
            "id": 3,
            "Name": "B+"
        },
        {
            "id": 4,
            "Name": "B-"
        },
        {
            "id": 5,
            "Name": "O+"
        },
        {
            "id": 6,
            "Name": "O-"
        },
        {
            "id": 7,
            "Name": "AB+"
        },
        {
            "id": 8,
            "Name": "AB-"
        }
    ]
}
```

### 3. Get Genders
**Endpoint:** `GET /api/genders`  
**Authentication:** Required

**Response:**
```json
{
    "success": true,
    "message": "Genders retrieved successfully",
    "data": [
        {
            "id": 1,
            "Name": "Male"
        },
        {
            "id": 2,
            "Name": "Female"
        }
    ]
}
```

### 4. Get Religions
**Note:** Based on the API routes, there doesn't appear to be a specific religions endpoint. You may need to:
- Create a religions API endpoint
- Use hardcoded values in the frontend
- Add religions as a static dropdown

**Example hardcoded religions array:**
```json
[
    { "id": 1, "Name": "Christian" },
    { "id": 2, "Name": "Muslim" },
    { "id": 3, "Name": "Jewish" },
    { "id": 4, "Name": "Buddhist" },
    { "id": 5, "Name": "Hindu" },
    { "id": 6, "Name": "Other" }
]
```

## Additional Parent APIs

### List All Parents
**Endpoint:** `GET /api/parents`  
**Authentication:** Required

**Response:**
```json
{
    "success": true,
    "message": "Parents retrieved successfully",
    "data": [
        {
            "id": 1,
            "Father_Name": {
                "en": "John Smith",
                "ar": "John Smith"
            },
            "Father_National_ID": "1234567890123",
            "Father_Phone": "+1234567890",
            "Father_Job": {
                "en": "Engineer",
                "ar": "Engineer"
            },
            "Father_Nationality": {
                "en": "American",
                "ar": "American"
            },
            "Father_Blood_Type": "A+",
            "Father_Address": "123 Main St, City",
            "Father_Religion": "Christian",
            "Mother_Name": {
                "en": "Jane Smith",
                "ar": "Jane Smith"
            },
            "Mother_National_ID": "9876543210987",
            "Mother_Phone": "+1234567891",
            "Mother_Job": {
                "en": "Teacher",
                "ar": "Teacher"
            },
            "Mother_Nationality": {
                "en": "American",
                "ar": "American"
            },
            "Mother_Blood_Type": "B+",
            "Mother_Address": "123 Main St, City",
            "Mother_Religion": "Christian",
            "students": [
                {
                    "id": 1,
                    "name": "Student Name",
                    "email": "student@example.com"
                }
            ]
        }
    ]
}
```

### Get Specific Parent
**Endpoint:** `GET /api/parents/{id}`  
**Authentication:** Required

### Update Parent
**Endpoint:** `PUT /api/parents/{id}`  
**Authentication:** Required

### Delete Parent
**Endpoint:** `DELETE /api/parents/{id}`  
**Authentication:** Required

## Implementation Notes

- The ParentController automatically handles bilingual storage (English and Arabic) for name and job fields
- National ID fields must be unique across all parents
- Password field is automatically hidden from responses for security
- The API uses standard Laravel API Resource patterns
- Error responses follow consistent JSON structure across all endpoints
- Father and Mother information are stored in the same parent record
- The parent can be associated with multiple students (shown in the index and show endpoints)

## Form Field Mapping

The parent creation form should map to these API fields:

**Father Section:**
- Father Name → `Father_Name`
- Father National ID → `Father_National_ID`
- Father Phone → `Father_Phone`
- Father Job → `Father_Job`
- Father Nationality → `Father_Nationality` (from nationalities API)
- Father Blood Type → `Father_Blood_Type` (from blood-types API)
- Father Address → `Father_Address`
- Father Religion → `Father_Religion` (static or from religions API)

**Mother Section:**
- Mother Name → `Mother_Name`
- Mother National ID → `Mother_National_ID`
- Mother Phone → `Mother_Phone`
- Mother Job → `Mother_Job`
- Mother Nationality → `Mother_Nationality` (from nationalities API)
- Mother Blood Type → `Mother_Blood_Type` (from blood-types API)
- Mother Address → `Mother_Address`
- Mother Religion → `Mother_Religion` (static or from religions API)

**Contact Section:**
- Email → `email` (valid email format, must be unique)

**Authentication Section:**
- Password → `password` (min 6 characters)
- Password Confirmation → `password_confirmation` (must match password)
