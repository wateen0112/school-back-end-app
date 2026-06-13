# School Management System - Detailed Screen Documentation

This document provides comprehensive details of all screens in the School Management System, including content, buttons, forms, and functionality.

## Table of Contents
- [Attendance](#attendance)
- [Fees](#fees)
- [Students](#students)
- [Teachers](#teachers)
- [Additional Modules](#additional-modules)

---

## Attendance

### 1. Attendance Index (`Attendance/index.blade.php`)

**Layout Structure:**
- Extends `layouts.master`
- Uses Toastr CSS for notifications
- RTL layout with Arabic text

**Content & Features:**
- **Header**: "قائمة الحضور والغياب للطلاب" (Student Attendance List)
- **Date Display**: Current date in red text: "تاريخ اليوم : {{ date('Y-m-d') }}"
- **Form**: POST to `Attendance.store` route with CSRF protection

**Table Structure:**
| Column | Arabic Label | Purpose |
|--------|--------------|---------|
| # | - | Row number |
| الاسم | Name | Student name |
| البريد الإلكتروني | Email | Student email |
| الجنس | Gender | Student gender |
| المرحلة الدراسية | Grade | Student grade |
| الفصل الدراسي | Classroom | Student classroom |
| القسم | Section | Student section |
| العمليات | Processes | Attendance actions |

**Interactive Elements:**
- **Radio Buttons** for each student:
  - "حضور" (Present) - Green text, value="presence"
  - "غياب" (Absent) - Red text, value="absent"
  - Disabled if attendance already recorded for today
- **Submit Button**: 
  - Text: "حفظ" (Save)
  - Class: `btn btn-success`
  - Type: Submit

**Hidden Fields:**
- `student_id[]` - Array of student IDs
- `grade_id` - Current grade ID
- `classroom_id` - Current classroom ID  
- `section_id` - Current section ID

**Error Handling:**
- Displays validation errors in alert-danger div
- Shows session status messages

**API Integration:**
- **GET Students**: `GET /api/{locale}/students` - Load students list
- **POST Attendance**: `POST /api/{locale}/attendance` - Save attendance data
- **GET Grades**: `GET /api/{locale}/grades` - Load grades for dropdown
- **GET Classrooms**: `GET /api/{locale}/classrooms` - Load classrooms for dropdown
- **GET Sections**: `GET /api/{locale}/sections` - Load sections for dropdown

**Required Headers:**
```
Authorization: Bearer {auth_token}
Content-Type: application/json
Accept: application/json
```

**POST Data Format:**
```json
{
  "student_id": [1, 2, 3],
  "attendance_status": ["presence", "absent", "presence"],
  "grade_id": 1,
  "classroom_id": 2,
  "section_id": 3,
  "attendance_date": "2024-03-15"
}
```

---

### 2. Attendance Sections (`Attendance/Sections.blade.php`)

**Layout Structure:**
- Extends `layouts.master`
- Accordion-style interface with plus/minus icons
- Grade-based organization

**Content & Features:**
- **Header**: "القسم: الحضور والغياب" (Section: Attendance)
- **Add Section Button**: 
  - Text: "إضافة قسم" (Add Section)
  - Class: `button x-small`
  - Triggers modal with `data-target="#exampleModal"`

**Accordion Structure:**
- **Grades as Expandable Sections**: Each grade shows as an accordion item
- **Table per Grade**: Shows sections within each grade

**Table Columns:**
| Column | Arabic Label | Purpose |
|--------|--------------|---------|
| # | - | Sequential number |
| اسم القسم | Section Name | Section display name |
| اسم الفصل | Class Name | Associated classroom |
| الحالة | Status | Active/Inactive status |
| العمليات | Operations | Action buttons |

**Status Badge:**
- **Active**: Green badge with "نشط"
- **Inactive**: Red badge with "غير نشط"

**Action Button:**
- **Students List**: 
  - Text: "قائمة الطلاب" (Students List)
  - Class: `btn btn-warning btn-sm`
  - Links to `Attendance.show` route with section ID

**AJAX Functionality:**
- Dynamic classroom loading based on grade selection
- Endpoint: `GET /api/{locale}/sections/classes/{Grade_id}`
- Updates classroom dropdown dynamically

**API Integration:**
- **GET Sections**: `GET /api/{locale}/sections` - Load sections with grades
- **GET Classes by Grade**: `GET /api/{locale}/sections/classes/{id}` - Filter classes by grade
- **GET Students by Section**: `GET /api/{locale}/students?section_id={id}` - Load students for specific section

**Required Headers:**
```
Authorization: Bearer {auth_token}
Content-Type: application/json
Accept: application/json
```

---

## Fees

### 1. Fees Index (`Fees/index.blade.php`)

**Note**: This file appears to be a course registration landing page rather than a fees management interface.

**Layout Structure:**
- Full HTML page with RTL layout
- Bootstrap-based responsive design
- Preloader animation

**Content:**
- **Main Title**: "الاشتراك في كورس برنامج المستشفيات" (Subscribe to Hospital Program Course)
- **Status Message**: "تم اغلاق باب الحجز سيتم الاعلان قريبا" (Registration is closed, announcement coming soon)
- **Error Display**: Shows session errors in dismissible alerts

**Design Elements:**
- Gray background section
- White contact form area
- Cairo font family throughout
- Responsive container layout

---

### 2. Add Fee (`Fees/add.blade.php`)

**Layout Structure:**
- Extends `layouts.master`
- Form-based interface with validation

**Form Fields:**

**Row 1 - Basic Information:**
- **Arabic Name**: "الاسم باللغة العربية" - Text input
- **English Name**: "الاسم باللغة الانجليزية" - Text input  
- **Amount**: "المبلغ" - Number input

**Row 2 - Classification:**
- **Grade Level**: "المرحلة الدراسية" - Dropdown populated from `$Grades`
- **Classroom**: "الصف الدراسي" - Dropdown (dynamically populated)
- **Academic Year**: "السنة الدراسية" - Dropdown with current and next year
- **Fee Type**: "نوع الرسوم" - Dropdown with options:
  - "رسوم دراسية" (Tuition Fees) - value="1"
  - "رسوم باص" (Bus Fees) - value="2"

**Additional Field:**
- **Notes**: "ملاحظات" - Textarea (4 rows)

**Buttons:**
- **Submit**: "تأكيد" (Confirm) - `btn btn-primary`
- Form uses POST method to `Fees.store` route

**Validation:**
- Displays all validation errors in alert-danger div
- Old input values preserved on validation failure

**API Integration:**
- **GET Grades**: `GET /api/{locale}/grades` - Load grades for dropdown
- **GET Classrooms**: `GET /api/{locale}/classrooms` - Load classrooms for dropdown
- **POST Fee**: `POST /api/{locale}/students/fees` - Create new fee
- **PUT Fee**: `PUT /api/{locale}/students/fees/{id}` - Update existing fee
- **DELETE Fee**: `DELETE /api/{locale}/students/fees/{id}` - Delete fee

**Required Headers:**
```
Authorization: Bearer {auth_token}
Content-Type: application/json
Accept: application/json
```

**POST Data Format:**
```json
{
  "title_ar": "رسوم دراسية",
  "title_en": "Tuition Fees",
  "amount": 5000,
  "Grade_id": 1,
  "Classroom_id": 2,
  "year": "2024",
  "fee_type": "1",
  "description": "رسوم الفصل الدراسي الأول"
}
```

---

### 3. Edit Fee (`Fees/edit.blade.php`)

**Layout Structure:**
- Similar to Add Fee but pre-populated with existing data
- Uses PUT method for update

**Pre-populated Fields:**
- **Arabic Name**: `$fee->getTranslation('title','ar')`
- **English Name**: `$fee->getTranslation('title','en')`
- **Amount**: `$fee->amount`
- **Grade**: Selected based on `$fee->Grade_id`
- **Classroom**: Shows current `$fee->classroom->Name_Class`
- **Year**: Selected based on `$fee->year`
- **Notes**: `$fee->description`

**Hidden Field:**
- `id` - Contains `$fee->id` for identification

**Form Method:**
- POST to `Fees.update` route
- `@method('PUT')` for RESTful update

---

### 4. Delete Fee (`Fees/Delete.blade.php`)

**Modal Structure:**
- Bootstrap modal for deletion confirmation
- Triggered by button with `data-target="#Delete_Fee{{$fee->id}}"`

**Modal Content:**
- **Header**: "حذف معلومات الطالب" (Delete Student Information)
- **Body**: Confirmation message "هل انت متاكد مع عملية الحذف ؟" (Are you sure about the deletion?)
- **Footer**: Two buttons:
  - **Close**: "إغلاق" - `btn btn-secondary`
  - **Submit**: "حفظ" - `btn btn-danger`

**Form Details:**
- POST to `Fees.destroy` route
- `@method('DELETE')` for RESTful deletion
- Hidden input with fee ID

**API Integration:**
- **DELETE Fee**: `DELETE /api/{locale}/students/fees/{id}` - Delete fee record

**Required Headers:**
```
Authorization: Bearer {auth_token}
Content-Type: application/json
Accept: application/json
```

**Response Format:**
```json
{
  "success": true,
  "message": "Fee deleted successfully",
  "data": null
}
```

---

## Students

### 1. Students Index (`Students/index.blade.php`)

**Layout Structure:**
- DataTables integration with 50 rows per page
- Dropdown-based action menu per student

**Header Button:**
- **Add Student**: 
  - Text: "إضافة طالب" (Add Student)
  - Class: `btn btn-success btn-sm`
  - Links to `Students.create` route

**Table Structure:**
| Column | Arabic Label | Purpose |
|--------|--------------|---------|
| # | - | Row number |
| الاسم | Name | Student name |
| البريد الإلكتروني | Email | Student email |
| الجنس | Gender | Student gender |
| المرحلة الدراسية | Grade | Student grade |
| الفصل الدراسي | Classroom | Student classroom |
| القسم | Section | Student section |
| العمليات | Operations | Action dropdown |

**Action Dropdown Menu:**
- **View Student**: 
  - Icon: `far fa-eye` (yellow)
  - Text: "عرض بيانات الطالب" (View Student Data)
  - Route: `Students.show`
- **Edit Student**: 
  - Icon: `fa fa-edit` (green)
  - Text: "تعديل بيانات الطالب" (Edit Student Data)
  - Route: `Students.edit`
- **Add Fee Invoice**: 
  - Icon: `fa fa-edit` (blue)
  - Text: "إضافة فاتورة رسوم" (Add Fee Invoice)
  - Route: `Fees_Invoices.show`
- **Receipt**: 
  - Icon: `fas fa-money-bill-alt` (light blue)
  - Text: "سند قبض" (Receipt)
  - Route: `receipt_students.show`
- **Processing Fee**: 
  - Icon: `fas fa-money-bill-alt` (light blue)
  - Text: "استبعاد رسوم" (Exclude Fees)
  - Route: `ProcessingFee.show`
- **Payment**: 
  - Icon: `fas fa-donate` (golden)
  - Text: "سند صرف" (Payment Voucher)
  - Route: `Payment_students.show`
- **Delete Student**: 
  - Icon: `fa fa-trash` (red)
  - Text: "حذف بيانات الطالب" (Delete Student Data)
  - Triggers delete modal

**API Integration:**
- **GET Students**: `GET /api/{locale}/students` - Load students list with pagination
- **GET Student**: `GET /api/{locale}/students/{id}` - Get student details
- **PUT Student**: `PUT /api/{locale}/students/{id}` - Update student information
- **DELETE Student**: `DELETE /api/{locale}/students/{id}` - Delete student record
- **GET Genders**: `GET /api/public/genders` - Load gender options
- **GET Nationalities**: `GET /api/public/nationalities` - Load nationality options
- **GET Blood Types**: `GET /api/public/blood-types` - Load blood type options
- **GET Parents**: `GET /api/{locale}/parents` - Load parent options

**Required Headers:**
```
Authorization: Bearer {auth_token}
Content-Type: application/json
Accept: application/json
```

**GET Students Query Parameters:**
```
GET /api/{locale}/students?page=1&per_page=50&grade_id=1&classroom_id=2&section_id=3
```

**PUT Student Data Format:**
```json
{
  "name_ar": "أحمد محمد",
  "name_en": "Ahmed Mohamed",
  "email": "ahmed@example.com",
  "password": "password123",
  "gender_id": 1,
  "nationalitie_id": 1,
  "blood_id": 1,
  "Date_Birth": "2010-01-01",
  "Grade_id": 1,
  "Classroom_id": 2,
  "section_id": 3,
  "parent_id": 1,
  "academic_year": "2024"
}
```

---

### 2. Add Student (`Students/add.blade.php`)

**Layout Structure:**
- Multi-section form with file upload capability
- CSRF protection and multipart form data

**Section 1 - Personal Information:**
**Header**: "المعلومات الشخصية" (Personal Information) - Blue text

**Fields:**
- **Arabic Name**: "الاسم بالعربية" - Required (red asterisk)
- **English Name**: "الاسم بالإنجليزية" - Required
- **Email**: "البريد الإلكتروني" - Email type
- **Password**: "كلمة المرور" - Password type
- **Gender**: "الجنس" - Required dropdown from `$Genders`
- **Nationality**: "الجنسية" - Required dropdown from `$nationals`
- **Blood Type**: "فصيلة الدم" - Dropdown from `$bloods`
- **Date of Birth**: "تاريخ الميلاد" - Date picker with format yyyy-mm-dd

**Section 2 - Student Information:**
**Header**: "معلومات الطالب" (Student Information) - Blue text

**Fields:**
- **Grade**: "المرحلة الدراسية" - Required dropdown from `$my_classes`
- **Classroom**: "الفصل الدراسي" - Required dropdown (dynamically populated)
- **Section**: "القسم" - Dropdown (dynamically populated)
- **Parent**: "ولي الأمر" - Required dropdown from `$parents`
- **Academic Year**: "السنة الأكاديمية" - Required dropdown (current + next year)

**Section 3 - Attachments:**
- **File Upload**: "المرفقات" - Required
- Accepts: `image/*`
- Multiple files supported: `name="photos[]"`

**Submit Button:**
- Text: "حفظ" (Save)
- Class: `btn btn-success btn-sm nextBtn btn-lg pull-right`

**Validation:**
- Comprehensive error display for all fields
- Old input values preserved
- Required fields marked with red asterisks

**API Integration:**
- **POST Student**: `POST /api/{locale}/students` - Create new student
- **POST Attachment**: `POST /api/{locale}/students/upload-attachment` - Upload student photos
- **GET Grades**: `GET /api/{locale}/grades` - Load grades for dropdown
- **GET Classrooms**: `GET /api/{locale}/classrooms` - Load classrooms for dropdown
- **GET Sections**: `GET /api/{locale}/sections` - Load sections for dropdown
- **GET Genders**: `GET /api/public/genders` - Load gender options
- **GET Nationalities**: `GET /api/public/nationalities` - Load nationality options
- **GET Blood Types**: `GET /api/public/blood-types` - Load blood type options
- **GET Parents**: `GET /api/{locale}/parents` - Load parent options

**Required Headers:**
```
Authorization: Bearer {auth_token}
Content-Type: multipart/form-data
Accept: application/json
```

**POST Student Data Format (multipart/form-data):**
```
name_ar: أحمد محمد
name_en: Ahmed Mohamed
email: ahmed@example.com
password: password123
gender_id: 1
nationalitie_id: 1
blood_id: 1
Date_Birth: 2010-01-01
Grade_id: 1
Classroom_id: 2
section_id: 3
parent_id: 1
academic_year: 2024
photos[]: [File, File]
```

---

## Teachers

### 1. Teachers Index (`Teachers/Teachers.blade.php`)

**Layout Structure:**
- DataTables with 50 rows per page
- Inline delete modals for each teacher

**Header Button:**
- **Add Teacher**: 
  - Text: "إضافة معلم" (Add Teacher)
  - Class: `btn btn-success btn-sm`
  - Route: `Teachers.create`

**Table Structure:**
| Column | Arabic Label | Purpose |
|--------|--------------|---------|
| # | - | Sequential number |
| اسم المعلم | Teacher Name | Teacher full name |
| الجنس | Gender | Teacher gender |
| تاريخ الانضمام | Joining Date | Employment date |
| التخصص | Specialization | Teacher specialization |
| العمليات | Operations | Action buttons |

**Action Buttons:**
- **Edit**: 
  - Icon: `fa fa-edit`
  - Class: `btn btn-info btn-sm`
  - Route: `Teachers.edit`
- **Delete**: 
  - Icon: `fa fa-trash`
  - Class: `btn btn-danger btn-sm`
  - Triggers delete modal

**Delete Modal:**
- **Title**: "حذف معلم" (Delete Teacher)
- **Warning**: "تحذير" (Warning) message
- **Close Button**: "إغلاق" - `btn btn-secondary`
- **Delete Button**: "حفظ" - `btn btn-danger`

**API Integration:**
- **GET Teachers**: `GET /api/{locale}/teachers` - Load teachers list
- **GET Teacher**: `GET /api/{locale}/teachers/{id}` - Get teacher details
- **POST Teacher**: `POST /api/{locale}/teachers` - Create new teacher
- **PUT Teacher**: `PUT /api/{locale}/teachers/{id}` - Update teacher information
- **DELETE Teacher**: `DELETE /api/{locale}/teachers/{id}` - Delete teacher record
- **GET Specializations**: `GET /api/public/specializations` - Load specialization options
- **GET Genders**: `GET /api/public/genders` - Load gender options

**Required Headers:**
```
Authorization: Bearer {auth_token}
Content-Type: application/json
Accept: application/json
```

**POST Teacher Data Format:**
```json
{
  "Email": "teacher@example.com",
  "Password": "password123",
  "Name_ar": "محمد أحمد",
  "Name_en": "Mohamed Ahmed",
  "Specialization_id": 1,
  "Gender_id": 1,
  "Joining_Date": "2024-01-01",
  "Address": "العنوان الكامل"
}
```

---

### 2. Create Teacher (`Teachers/create.blade.php`)

**Layout Structure:**
- Two-column form layout with validation
- CSRF protection and error handling

**Row 1 - Account Information:**
- **Email**: "البريد الإلكتروني" - Email input with validation
- **Password**: "كلمة المرور" - Password input with validation

**Row 2 - Personal Information:**
- **Arabic Name**: "الاسم بالعربية" - Text input with validation
- **English Name**: "الاسم بالإنجليزية" - Text input with validation

**Row 3 - Professional Information:**
- **Specialization**: "التخصص" - Dropdown from `$specializations`
- **Gender**: "الجنس" - Dropdown from `$genders`

**Row 4 - Employment Details:**
- **Joining Date**: "تاريخ الانضمام" - Date picker (yyyy-mm-dd format)

**Additional Field:**
- **Address**: "العنوان" - Textarea (4 rows)

**Submit Button:**
- Text: "التالي" (Next)
- Class: `btn btn-success btn-sm nextBtn btn-lg pull-right`

**Validation Features:**
- Individual error messages for each field
- Error display in `alert alert-danger` divs
- Form data preserved on validation failure

**API Integration:**
- **POST Teacher**: `POST /api/{locale}/teachers` - Create new teacher
- **GET Specializations**: `GET /api/public/specializations` - Load specialization options
- **GET Genders**: `GET /api/public/genders` - Load gender options

**Required Headers:**
```
Authorization: Bearer {auth_token}
Content-Type: application/json
Accept: application/json
```

**Response Format:**
```json
{
  "success": true,
  "message": "Teacher created successfully",
  "data": {
    "id": 1,
    "Email": "teacher@example.com",
    "Name_ar": "محمد أحمد",
    "Name_en": "Mohamed Ahmed",
    "created_at": "2024-03-15T10:30:00.000000Z"
  }
}
```

---

### 3. Edit Teacher (`Teachers/Edit.blade.php`)

**Layout Structure:**
- Similar to create form but pre-populated with existing data
- Uses PATCH method for RESTful update

**Pre-populated Fields:**
- **Email**: Current teacher email
- **Password**: Current password (editable)
- **Arabic Name**: `$Teachers->getTranslation('Name', 'ar')`
- **English Name**: `$Teachers->getTranslation('Name', 'en')`
- **Specialization**: Current specialization selected
- **Gender**: Current gender selected
- **Joining Date**: Current joining date
- **Address**: Current address text

**Hidden Field:**
- Teacher ID for identification

**Form Method:**
- POST to `Teachers.update` route
- `@method('patch')` for RESTful update

**API Integration:**
- **PUT Teacher**: `PUT /api/{locale}/teachers/{id}` - Update teacher information
- **GET Specializations**: `GET /api/public/specializations` - Load specialization options
- **GET Genders**: `GET /api/public/genders` - Load gender options

**Required Headers:**
```
Authorization: Bearer {auth_token}
Content-Type: application/json
Accept: application/json
```

**PUT Teacher Data Format:**
```json
{
  "id": 1,
  "Email": "teacher@example.com",
  "Password": "newpassword123",
  "Name_ar": "محمد أحمد",
  "Name_en": "Mohamed Ahmed",
  "Specialization_id": 1,
  "Gender_id": 1,
  "Joining_Date": "2024-01-01",
  "Address": "العنوان المحدث"
}
```

---

## Additional Modules

### **Fees Invoices Module**
**API Integration:**
- **GET Invoices**: `GET /api/{locale}/students/fees-invoices` - Load fee invoices
- **POST Invoice**: `POST /api/{locale}/students/fees-invoices` - Create fee invoice
- **GET Invoice**: `GET /api/{locale}/students/fees-invoices/{id}` - Get invoice details
- **PUT Invoice**: `PUT /api/{locale}/students/fees-invoices/{id}` - Update invoice
- **DELETE Invoice**: `DELETE /api/{locale}/students/fees-invoices/{id}` - Delete invoice

**Required Headers:**
```
Authorization: Bearer {auth_token}
Content-Type: application/json
Accept: application/json
```

**POST Invoice Data Format:**
```json
{
  "student_id": 1,
  "fee_id": 1,
  "amount": 5000,
  "description": "رسوم الفصل الدراسي الأول",
  "due_date": "2024-03-15"
}
```

### **Processing Fees Module**
**API Integration:**
- **GET Processing Fees**: `GET /api/{locale}/students/processing-fee` - Load processing fees
- **POST Processing Fee**: `POST /api/{locale}/students/processing-fee` - Create processing fee
- **GET Processing Fee**: `GET /api/{locale}/students/processing-fee/{id}` - Get details
- **PUT Processing Fee**: `PUT /api/{locale}/students/processing-fee/{id}` - Update
- **DELETE Processing Fee**: `DELETE /api/{locale}/students/processing-fee/{id}` - Delete

**POST Processing Fee Data Format:**
```json
{
  "student_id": 1,
  "amount": 500,
  "description": "رسوم معالجة",
  "date": "2024-03-15"
}
```

### **Payment Students Module**
**API Integration:**
- **GET Payments**: `GET /api/{locale}/students/payment-students` - Load payments
- **POST Payment**: `POST /api/{locale}/students/payment-students` - Create payment
- **GET Payment**: `GET /api/{locale}/students/payment-students/{id}` - Get details
- **PUT Payment**: `PUT /api/{locale}/students/payment-students/{id}` - Update
- **DELETE Payment**: `DELETE /api/{locale}/students/payment-students/{id}` - Delete

**POST Payment Data Format:**
```json
{
  "student_id": 1,
  "amount": 1000,
  "description": "سند صرف",
  "date": "2024-03-15",
  "payment_type": "cash"
}
```

### **Receipt Students Module**
**API Integration:**
- **GET Receipts**: `GET /api/{locale}/students/receipt-students` - Load receipts
- **POST Receipt**: `POST /api/{locale}/students/receipt-students` - Create receipt
- **GET Receipt**: `GET /api/{locale}/students/receipt-students/{id}` - Get details
- **PUT Receipt**: `PUT /api/{locale}/students/receipt-students/{id}` - Update
- **DELETE Receipt**: `DELETE /api/{locale}/students/receipt-students/{id}` - Delete

**POST Receipt Data Format:**
```json
{
  "student_id": 1,
  "amount": 5000,
  "description": "سند قبض رسوم",
  "date": "2024-03-15",
  "fee_type": "tuition"
}
```

### **Library Module**
**API Integration:**
- **GET Books**: `GET /api/{locale}/students/library` - Load library books
- **POST Book**: `POST /api/{locale}/students/library` - Add new book
- **GET Book**: `GET /api/{locale}/students/library/{id}` - Get book details
- **PUT Book**: `PUT /api/{locale}/students/library/{id}` - Update book
- **DELETE Book**: `DELETE /api/{locale}/students/library/{id}` - Delete book
- **Download Attachment**: `GET /api/{locale}/library/download/{filename}` - Download file

**POST Book Data Format:**
```json
{
  "title": "كتاب الرياضيات",
  "author": "أحمد محمد",
  "grade_id": 1,
  "classroom_id": 2,
  "description": "كتاب دراسي لمادة الرياضيات",
  "file": "File"
}
```

### **Online Classes Module**
**API Integration:**
- **GET Online Classes**: `GET /api/{locale}/students/online-classes` - Load online classes
- **POST Online Class**: `POST /api/{locale}/students/online-classes` - Create class
- **GET Online Class**: `GET /api/{locale}/students/online-classes/{id}` - Get details
- **PUT Online Class**: `PUT /api/{locale}/students/online-classes/{id}` - Update
- **DELETE Online Class**: `DELETE /api/{locale}/students/online-classes/{id}` - Delete
- **GET Indirect**: `GET /api/{locale}/online-classes/indirect-admin` - Get indirect classes
- **POST Indirect**: `POST /api/{locale}/online-classes/indirect-admin` - Create indirect class

**POST Online Class Data Format:**
```json
{
  "grade_id": 1,
  "classroom_id": 2,
  "section_id": 3,
  "subject_id": 1,
  "teacher_id": 1,
  "meeting_link": "https://zoom.us/j/123456789",
  "date": "2024-03-15",
  "time": "10:00 AM"
}
```

### **Quizzes Module**
**API Integration:**
- **GET Quizzes**: `GET /api/{locale}/quizzes` - Load quizzes
- **POST Quiz**: `POST /api/{locale}/quizzes` - Create quiz
- **GET Quiz**: `GET /api/{locale}/quizzes/{id}` - Get quiz details
- **PUT Quiz**: `PUT /api/{locale}/quizzes/{id}` - Update quiz
- **DELETE Quiz**: `DELETE /api/{locale}/quizzes/{id}` - Delete quiz

**POST Quiz Data Format:**
```json
{
  "grade_id": 1,
  "classroom_id": 2,
  "subject_id": 1,
  "teacher_id": 1,
  "title": "اختبار الرياضيات",
  "description": "اختبار الفصل الأول",
  "date": "2024-03-15",
  "duration": 60
}
```

### **Questions Module**
**API Integration:**
- **GET Questions**: `GET /api/{locale}/questions` - Load questions
- **POST Question**: `POST /api/{locale}/questions` - Create question
- **GET Question**: `GET /api/{locale}/questions/{id}` - Get question details
- **PUT Question**: `PUT /api/{locale}/questions/{id}` - Update question
- **DELETE Question**: `DELETE /api/{locale}/questions/{id}` - Delete question

**POST Question Data Format:**
```json
{
  "quiz_id": 1,
  "question_text": "ما هو ناتج 2 + 2؟",
  "options": ["3", "4", "5", "6"],
  "correct_answer": "4",
  "points": 10
}
```

### **Parents Module**
**API Integration:**
- **GET Parents**: `GET /api/{locale}/parents` - Load parents
- **POST Parent**: `POST /api/{locale}/parents` - Create parent
- **GET Parent**: `GET /api/{locale}/parents/{id}` - Get parent details
- **PUT Parent**: `PUT /api/{locale}/parents/{id}` - Update parent
- **DELETE Parent**: `DELETE /api/{locale}/parents/{id}` - Delete parent

**POST Parent Data Format:**
```json
{
  "Name_Father": "أحمد محمد",
  "Name_Mother": "فاطمة أحمد",
  "Father_Job": "مهندس",
  "Mother_Job": "معلمة",
  "Father_National_ID": "12345678901234",
  "Mother_National_ID": "12345678901234",
  "Father_Phone": "01234567890",
  "Mother_Phone": "01234567891",
  "Address": "العنوان الكامل"
}
```

### **Subjects Module**
**API Integration:**
- **GET Subjects**: `GET /api/{locale}/subjects` - Load subjects
- **POST Subject**: `POST /api/{locale}/subjects` - Create subject
- **GET Subject**: `GET /api/{locale}/subjects/{id}` - Get subject details
- **PUT Subject**: `PUT /api/{locale}/subjects/{id}` - Update subject
- **DELETE Subject**: `DELETE /api/{locale}/subjects/{id}` - Delete subject

**POST Subject Data Format:**
```json
{
  "Name_ar": "الرياضيات",
  "Name_en": "Mathematics",
  "Grade_id": 1,
  "Classroom_id": 2
}
```

### **Dashboard Module**
**API Integration:**
- **GET Dashboard**: `GET /api/{locale}/dashboard` - Load dashboard data
- **GET Public Info**: `GET /api/public/info` - Get public information

**Dashboard Response Format:**
```json
{
  "students_count": 1248,
  "teachers_count": 86,
  "attendance_rate": 94,
  "pending_fees": 15000,
  "recent_activities": [
    {
      "user": "أحمد منصور",
      "activity": "تحديث درجات الفصل الأول",
      "status": "مكتمل",
      "time": "منذ 10 دقائق"
    }
  ]
}
```

---

## **Authentication API**

### **Login API**
- **POST Login**: `POST /api/login/{type}` - User authentication
- **POST Logout**: `POST /api/logout/{type}` - User logout
- **POST Refresh**: `POST /api/refresh` - Refresh token
- **POST Me**: `POST /api/me` - Get current user info

**Login Data Format:**
```json
{
  "email": "user@example.com",
  "password": "password123",
  "type": "student" // or teacher, parent
}
```

**Login Response Format:**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "name": "أحمد محمد",
      "email": "ahmed@example.com",
      "type": "student"
    },
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "token_type": "bearer",
    "expires_in": 3600
  }
}
```

---

## **Common API Patterns**

*All modules follow similar patterns with:*

- **CRUD Operations**: Create, Read, Update, Delete functionality
- **Data Tables**: Responsive DataTables with pagination
- **Modal Dialogs**: For confirmations and quick actions
- **Form Validation**: Comprehensive error handling
- **Dropdown Dependencies**: Dynamic field population
- **File Uploads**: Document and image attachments
- **Multi-language Support**: Arabic/English labels
- **Responsive Design**: Mobile-friendly layouts

## Common Patterns Across All Screens

### 1. **Layout Structure**
- All screens extend `layouts.master`
- Consistent header/footer structure
- Toastr notifications integration
- RTL language support

### 2. **Form Elements**
- CSRF protection on all forms
- Bootstrap form classes
- Validation error displays
- Old input preservation
- Required field indicators

### 3. **Data Tables**
- DataTables.net integration
- Responsive design
- Search and pagination
- Sortable columns
- Action buttons per row

### 4. **Interactive Elements**
- Bootstrap modals for confirmations
- Dropdown menus for actions
- AJAX for dynamic content
- Date pickers for date fields
- File upload interfaces

### 5. **Styling**
- Bootstrap 4 framework
- Cairo font family for Arabic
- Consistent color scheme
- Icon integration (Font Awesome)
- Responsive grid system

### 6. **Navigation**
- Breadcrumb navigation
- Back buttons where appropriate
- Consistent routing patterns
- Role-based access control

---

## Technical Implementation Notes

### **Security Features**
- CSRF tokens on all forms
- Input validation and sanitization
- SQL injection prevention through Eloquent ORM
- XSS protection with Blade escaping

### **Performance Optimizations**
- DataTables with server-side processing
- Lazy loading for large datasets
- Optimized database queries
- Asset minification

### **Accessibility**
- Semantic HTML5 structure
- ARIA labels where needed
- Keyboard navigation support
- Screen reader compatibility

### **Browser Compatibility**
- Modern browser support
- Graceful degradation
- Mobile responsive design
- Touch-friendly interfaces

This documentation provides a comprehensive overview of the School Management System's user interface, detailing the functionality, layout, and interactive elements of each screen component.
