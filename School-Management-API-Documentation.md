# 📋 School Management API Documentation

## 🔐 **Authentication**
**Base URL:** `http://10.96.0.21:8000/api`

### **Login First**
```bash
POST /api/auth/login
Content-Type: application/json

{
  "email": "admin@example.com",
  "password": "password123"
}
```

### **Login Response**
```json
{
  "success": true,
  "message": "Login successful",
  "access_token": "27|Y4gWULYNskujrkkIpgFDgeQYmTituViyXAxD0L8T",
  "bearer_token": "Bearer 27|Y4gWULYNskujrkkIpgFDgeQYmTituViyXAxD0L8T",
  "token_type": "Bearer",
  "type": "admin",
  "user": {
    "id": 7,
    "name": "Test Admin",
    "email": "admin@example.com",
    "created_at": "2026-05-11T14:11:42.000000Z",
    "updated_at": "2026-05-11T14:11:42.000000Z"
  }
}
```

### **Using Bearer Token**
For all protected endpoints, include the Authorization header:
```bash
Authorization: Bearer {your_access_token}
```

---

## 🎓 **1. Get Grades API**

### **Request**
```bash
GET /api/grades
Authorization: Bearer {your_token}
Content-Type: application/json
```

### **Response**
```json
{
  "success": true,
  "message": "Grades retrieved successfully",
  "data": [
    {
      "id": 13,
      "Name": {
        "en": "Primary stage",
        "ar": "المرحلة الابتدائية"
      },
      "Notes": null,
      "classrooms": [
        {
          "id": 13,
          "Name_Class": {
            "en": "First grade",
            "ar": "الصف الاول"
          },
          "Grade_id": 13,
          "created_at": "2026-05-08T11:32:11.000000Z",
          "updated_at": "2026-05-08T11:32:11.000000Z"
        },
        {
          "id": 14,
          "Name_Class": {
            "en": "Second grade",
            "ar": "الصف الثاني"
          },
          "Grade_id": 13,
          "created_at": "2026-05-08T11:32:11.000000Z",
          "updated_at": "2026-05-08T11:32:11.000000Z"
        }
      ],
      "created_at": "2026-05-08T11:32:11.000000Z",
      "updated_at": "2026-05-08T11:32:11.000000Z"
    },
    {
      "id": 14,
      "Name": {
        "en": "middle School",
        "ar": "المرحلة الاعدادية"
      },
      "Notes": null,
      "classrooms": [
        {
          "id": 15,
          "Name_Class": {
            "en": "Third grade",
            "ar": "الصف الثالث"
          },
          "Grade_id": 14,
          "created_at": "2026-05-08T11:32:11.000000Z",
          "updated_at": "2026-05-08T11:32:11.000000Z"
        }
      ],
      "created_at": "2026-05-08T11:32:11.000000Z",
      "updated_at": "2026-05-08T11:32:11.000000Z"
    },
    {
      "id": 15,
      "Name": {
        "en": "High school",
        "ar": "المرحلة الثانوية"
      },
      "Notes": null,
      "classrooms": [],
      "created_at": "2026-05-08T11:32:11.000000Z",
      "updated_at": "2026-05-08T11:32:11.000000Z"
    }
  ]
}
```

---

## 📚 **2. Get Sections API**

### **Request**
```bash
GET /api/sections
Authorization: Bearer {your_token}
Content-Type: application/json
```

### **Response**
```json
{
  "success": true,
  "message": "Sections retrieved successfully",
  "data": [
    {
      "id": 13,
      "Name_Section": {
        "en": "Section A",
        "ar": "القسم أ"
      },
      "Grade_id": 13,
      "Class_id": 13,
      "created_at": "2026-05-08T11:32:11.000000Z",
      "updated_at": "2026-05-08T11:32:11.000000Z",
      "My_classs": {
        "id": 13,
        "Name_Class": {
          "en": "First grade",
          "ar": "الصف الاول"
        },
        "Grade_id": 13,
        "created_at": "2026-05-08T11:32:11.000000Z",
        "updated_at": "2026-05-08T11:32:11.000000Z"
      },
      "Grades": {
        "id": 13,
        "Name": {
          "en": "Primary stage",
          "ar": "المرحلة الابتدائية"
        },
        "Notes": null,
        "created_at": "2026-05-08T11:32:11.000000Z",
        "updated_at": "2026-05-08T11:32:11.000000Z"
      }
    }
  ]
}
```

---

## 👨‍🎓 **3. Create New Student API**

### **Request**
```bash
POST /api/students
Authorization: Bearer {your_token}
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john.doe@school.com",
  "password": "password123",
  "gender_id": 1,
  "nationalitie_id": 1,
  "blood_id": 1,
  "Date_Birth": "2010-01-01",
  "Grade_id": 13,
  "Classroom_id": 13,
  "section_id": 13,
  "parent_id": 4,
  "academic_year": "2024"
}
```

### **Response**
```json
{
  "success": true,
  "message": "Student created successfully",
  "data": {
    "id": 5,
    "name": {
      "en": "John Doe",
      "ar": "John Doe"
    },
    "email": "john.doe@school.com",
    "gender_id": 1,
    "nationalitie_id": 1,
    "blood_id": 1,
    "Date_Birth": "2010-01-01",
    "Grade_id": 13,
    "Classroom_id": 13,
    "section_id": 13,
    "parent_id": 4,
    "academic_year": "2024",
    "created_at": "2026-05-11T14:45:00.000000Z",
    "updated_at": "2026-05-11T14:45:00.000000Z",
    "grade": {
      "id": 13,
      "Name": {
        "en": "Primary stage",
        "ar": "المرحلة الابتدائية"
      }
    },
    "classroom": {
      "id": 13,
      "Name_Class": {
        "en": "First grade",
        "ar": "الصف الاول"
      }
    },
    "section": {
      "id": 13,
      "Name_Section": {
        "en": "Section A",
        "ar": "القسم أ"
      }
    }
  }
}
```

---

## 📝 **Required Field Descriptions**

| Field | Type | Description | Example |
|-------|------|-------------|---------|
| `name` | String | Student name (bilingual) | "John Doe" |
| `email` | String | Unique email address | "john.doe@school.com" |
| `password` | String | Student password | "password123" |
| `gender_id` | Integer | Gender ID from genders table | 1 (Male), 2 (Female) |
| `nationalitie_id` | Integer | Nationality ID from nationalities table | 1 (Local) |
| `blood_id` | Integer | Blood type ID from blood types table | 1 (A+) |
| `Date_Birth` | Date | Birth date (YYYY-MM-DD) | "2010-01-01" |
| `Grade_id` | Integer | Grade ID from grades table | 13 (Primary) |
| `Classroom_id` | Integer | Classroom ID from classrooms table | 13 (First grade) |
| `section_id` | Integer | Section ID from sections table | 13 (Section A) |
| `parent_id` | Integer | Parent ID from parents table | 4 |
| `academic_year` | String | Academic year | "2024" |

---

## 🔧 **Additional Student Operations**

### **Get Single Student**
```bash
GET /api/students/{id}
Authorization: Bearer {your_token}
```

### **Response**
```json
{
  "success": true,
  "message": "Student retrieved successfully",
  "data": {
    "id": 4,
    "name": {
      "ar": "احمد ابراهيم",
      "en": "Ahmed Ibrahim"
    },
    "email": "student@school.local",
    "gender_id": 7,
    "nationalitie_id": 739,
    "blood_id": 25,
    "Date_Birth": "1995-01-01",
    "Grade_id": 13,
    "Classroom_id": 15,
    "section_id": 13,
    "parent_id": 4,
    "academic_year": "2021",
    "grade": {...},
    "classroom": {...},
    "section": {...},
    "gender": {...},
    "Nationality": {...},
    "myparent": {...}
  }
}
```

### **Update Student**
```bash
PUT /api/students/{id}
Authorization: Bearer {your_token}
Content-Type: application/json

{
  "name": "Updated Name",
  "email": "updated.email@school.com",
  "gender_id": 1,
  "nationalitie_id": 1,
  "blood_id": 1,
  "Date_Birth": "2010-01-01",
  "Grade_id": 13,
  "Classroom_id": 13,
  "section_id": 13,
  "parent_id": 4,
  "academic_year": "2024"
}
```

### **Delete Student**
```bash
DELETE /api/students/{id}
Authorization: Bearer {your_token}
```

### **Response**
```json
{
  "success": true,
  "message": "Student deleted successfully"
}
```

---

## 🏠 **4. Dashboard API**

### **Request**
```bash
GET /api/dashboard
Authorization: Bearer {your_token}
Content-Type: application/json
```

### **Response**
```json
{
  "success": true,
  "message": "Dashboard data retrieved successfully",
  "user": {
    "id": 7,
    "name": "Test Admin",
    "email": "admin@example.com",
    "created_at": "2026-05-11T14:11:42.000000Z",
    "updated_at": "2026-05-11T14:11:42.000000Z"
  },
  "stats": {
    "students_count": 100,
    "teachers_count": 20,
    "classes_count": 15
  }
}
```

---

## 👪 **5. Parents API**

### **Get All Parents**
```bash
GET /api/parents
Authorization: Bearer {your_token}
```

### **Response**
```json
{
  "success": true,
  "message": "Parents retrieved successfully",
  "data": [
    {
      "id": 4,
      "email": "parent@school.local",
      "Name_Father": {
        "en": "emad",
        "ar": "عماد محمد"
      },
      "National_ID_Father": "1234567810",
      "Phone_Father": "1234567810",
      "Job_Father": {
        "en": "programmer",
        "ar": "مبرمج"
      },
      "Name_Mother": {
        "en": "SS",
        "ar": "سس"
      },
      "Job_Mother": {
        "en": "Teacher",
        "ar": "معلمة"
      },
      "students": [
        {
          "id": 4,
          "name": {
            "ar": "احمد ابراهيم",
            "en": "Ahmed Ibrahim"
          },
          "email": "student@school.local",
          "Grade_id": 13,
          "Classroom_id": 15,
          "section_id": 13,
          "parent_id": 4
        }
      ]
    }
  ]
}
```

---

## 🌐 **6. Public APIs (No Authentication Required)**

### **Get Public Grades**
```bash
GET /api/public/grades
Content-Type: application/json
```

### **Response**
```json
{
  "success": true,
  "message": "Public grades retrieved successfully",
  "data": [
    {
      "id": 13,
      "Name": {
        "en": "Primary stage",
        "ar": "المرحلة الابتدائية"
      }
    },
    {
      "id": 14,
      "Name": {
        "en": "middle School",
        "ar": "المرحلة الاعدادية"
      }
    }
  ]
}
```

### **Get Public Subjects**
```bash
GET /api/public/subjects
Content-Type: application/json
```

---

## 📋 **7. Other Available APIs**

### **Teachers Management**
```bash
GET /api/teachers          # List all teachers
POST /api/teachers         # Create new teacher
GET /api/teachers/{id}     # Get specific teacher
PUT /api/teachers/{id}     # Update teacher
DELETE /api/teachers/{id}  # Delete teacher
```

### **Subjects Management**
```bash
GET /api/subjects          # List all subjects
POST /api/subjects         # Create new subject
GET /api/subjects/{id}     # Get specific subject
PUT /api/subjects/{id}     # Update subject
DELETE /api/subjects/{id}  # Delete subject
```

### **Classrooms Management**
```bash
GET /api/classrooms                    # List all classrooms
POST /api/classrooms                   # Create new classroom
GET /api/classrooms/{id}               # Get specific classroom
PUT /api/classrooms/{id}               # Update classroom
DELETE /api/classrooms/{id}            # Delete classroom
POST /api/classrooms/delete_all        # Delete multiple classrooms
POST /api/classrooms/filter            # Filter classrooms
```

---

## ⚠️ **Error Responses**

### **Validation Error (422)**
```json
{
  "success": false,
  "message": "Validation errors",
  "errors": {
    "email": ["The email field is required."],
    "name": ["The name field is required."]
  }
}
```

### **Authentication Error (401)**
```json
{
  "success": false,
  "message": "Invalid email or password"
}
```

### **Not Found Error (404)**
```json
{
  "success": false,
  "message": "Resource not found",
  "error": "No query results for model [App\Models\\Student]"
}
```

### **Server Error (500)**
```json
{
  "success": false,
  "message": "Internal server error",
  "error": "Database connection failed"
}
```

---

## ✅ **API Status Summary**

| Endpoint | Status | Description |
|----------|--------|-------------|
| `POST /api/auth/login` | ✅ Working | Unified login with auto user type detection |
| `GET /api/dashboard` | ✅ Working | Dashboard with user info and stats |
| `GET /api/grades` | ✅ Working | List all grades with classrooms |
| `GET /api/sections` | ⚠️ Issue | Returns 500 error (needs debugging) |
| `GET /api/students` | ⚠️ Issue | Returns 500 error (needs debugging) |
| `POST /api/students` | ✅ Ready | Create new student (model fixed) |
| `GET /api/parents` | ✅ Working | List parents with students |
| `GET /api/teachers` | ✅ Working | List all teachers |
| `GET /api/subjects` | ✅ Working | List all subjects |
| `GET /api/public/*` | ✅ Working | Public endpoints (no auth required) |

---

## 📱 **Mobile App Integration Notes**

1. **Authentication Flow**: Login → Get Bearer Token → Use in all subsequent requests
2. **Auto User Type Detection**: System automatically detects admin, teacher, student, or parent
3. **Bilingual Support**: All text fields support English and Arabic
4. **Standardized Responses**: All endpoints return consistent JSON format
5. **Error Handling**: Comprehensive error responses with proper HTTP status codes

---

## 🔧 **Development Notes**

- **Framework**: Laravel 8+ with Sanctum for API authentication
- **Database**: MySQL with proper foreign key relationships
- **Localization**: Full Arabic/English support using Spatie Translatable
- **API Standards**: RESTful design with proper HTTP methods
- **Security**: Bearer token authentication with proper validation

---

*Last Updated: May 11, 2026*
*API Version: 1.0*
*Base URL: http://10.96.0.21:8000/api*
