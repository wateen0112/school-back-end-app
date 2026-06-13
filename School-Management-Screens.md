# School Management System - All Screens Documentation

This document provides a comprehensive overview of all screens available in the School Management System, organized by module.

## Table of Contents
- [Attendance](#attendance)
- [Fees](#fees)
- [Fees Invoices](#fees-invoices)
- [Grades](#grades)
- [My Classes](#my-classes)
- [Payment](#payment)
- [Processing Fee](#processing-fee)
- [Questions](#questions)
- [Quizzes](#quizzes)
- [Receipt](#receipt)
- [Sections](#sections)
- [Students](#students)
- [Subjects](#subjects)
- [Teachers](#teachers)
- [Library](#library)
- [Online Classes](#online-classes)
- [Parents](#parents)
- [Settings](#settings)

---

## Attendance

### 1. Attendance Index (`Attendance/index.blade.php`)
- **Purpose**: Main attendance management screen
- **Features**: View attendance records, mark attendance, filter by date/class
- **Path**: `/pages/Attendance/index.blade.php`

### 2. Attendance Sections (`Attendance/Sections.blade.php`)
- **Purpose**: Attendance management by sections
- **Features**: Section-wise attendance view and management
- **Path**: `/pages/Attendance/Sections.blade.php`

---

## Fees

### 1. Fees Index (`Fees/index.blade.php`)
- **Purpose**: List and manage all fee types
- **Features**: View fee structures, edit/delete fees
- **Path**: `/pages/Fees/index.blade.php`

### 2. Add Fee (`Fees/add.blade.php`)
- **Purpose**: Create new fee types
- **Features**: Fee creation form with validation
- **Path**: `/pages/Fees/add.blade.php`

### 3. Edit Fee (`Fees/edit.blade.php`)
- **Purpose**: Update existing fee information
- **Features**: Fee editing form
- **Path**: `/pages/Fees/edit.blade.php`

### 4. Delete Fee (`Fees/Delete.blade.php`)
- **Purpose**: Fee deletion confirmation
- **Features**: Fee deletion interface
- **Path**: `/pages/Fees/Delete.blade.php`

---

## Fees Invoices

### 1. Fees Invoices Index (`Fees_Invoices/index.blade.php`)
- **Purpose**: Manage student fee invoices
- **Features**: View all invoices, filter by student/status
- **Path**: `/pages/Fees_Invoices/index.blade.php`

### 2. Add Fee Invoice (`Fees_Invoices/add.blade.php`)
- **Purpose**: Create new fee invoices
- **Features**: Invoice creation form
- **Path**: `/pages/Fees_Invoices/add.blade.php`

### 3. Edit Fee Invoice (`Fees_Invoices/edit.blade.php`)
- **Purpose**: Update existing invoices
- **Features**: Invoice editing interface
- **Path**: `/pages/Fees_Invoices/edit.blade.php`

### 4. Delete Fee Invoice (`Fees_Invoices/Delete.blade.php`)
- **Purpose**: Remove fee invoices
- **Features**: Invoice deletion confirmation
- **Path**: `/pages/Fees_Invoices/Delete.blade.php`

---

## Grades

### 1. Grades Management (`Grades/Grades.blade.php`)
- **Purpose**: Manage academic grades
- **Features**: CRUD operations for grade levels
- **Path**: `/pages/Grades/Grades.blade.php`

---

## My Classes

### 1. My Classes (`My_Classes/My_Classes.blade.php`)
- **Purpose**: Teacher's class management
- **Features**: View assigned classes, manage class activities
- **Path**: `/pages/My_Classes/My_Classes.blade.php`

---

## Payment

### 1. Payment Index (`Payment/index.blade.php`)
- **Purpose**: View all payment records
- **Features**: Payment history, filtering, search
- **Path**: `/pages/Payment/index.blade.php`

### 2. Add Payment (`Payment/add.blade.php`)
- **Purpose**: Record new payments
- **Features**: Payment entry form
- **Path**: `/pages/Payment/add.blade.php`

### 3. Edit Payment (`Payment/edit.blade.php`)
- **Purpose**: Update payment records
- **Features**: Payment modification interface
- **Path**: `/pages/Payment/edit.blade.php`

### 4. Delete Payment (`Payment/Delete.blade.php`)
- **Purpose**: Remove payment records
- **Features**: Payment deletion confirmation
- **Path**: `/pages/Payment/Delete.blade.php`

---

## Processing Fee

### 1. Processing Fee Index (`ProcessingFee/index.blade.php`)
- **Purpose**: Manage processing fees
- **Features**: View all processing fees
- **Path**: `/pages/ProcessingFee/index.blade.php`

### 2. Add Processing Fee (`ProcessingFee/add.blade.php`)
- **Purpose**: Create new processing fees
- **Features**: Processing fee creation form
- **Path**: `/pages/ProcessingFee/add.blade.php`

### 3. Edit Processing Fee (`ProcessingFee/edit.blade.php`)
- **Purpose**: Update processing fees
- **Features**: Processing fee editing
- **Path**: `/pages/ProcessingFee/edit.blade.php`

### 4. Delete Processing Fee (`ProcessingFee/Delete.blade.php`)
- **Purpose**: Remove processing fees
- **Features**: Processing fee deletion
- **Path**: `/pages/ProcessingFee/Delete.blade.php`

---

## Questions

### 1. Questions Index (`Questions/index.blade.php`)
- **Purpose**: Manage quiz questions
- **Features**: View all questions, filter by quiz
- **Path**: `/pages/Questions/index.blade.php`

### 2. Create Question (`Questions/create.blade.php`)
- **Purpose**: Add new quiz questions
- **Features**: Question creation form with multiple choice options
- **Path**: `/pages/Questions/create.blade.php`

### 3. Edit Question (`Questions/edit.blade.php`)
- **Purpose**: Modify existing questions
- **Features**: Question editing interface
- **Path**: `/pages/Questions/edit.blade.php`

### 4. Delete Question (`Questions/destroy.blade.php`)
- **Purpose**: Remove quiz questions
- **Features**: Question deletion confirmation
- **Path**: `/pages/Questions/destroy.blade.php`

---

## Quizzes

### 1. Quizzes Index (`Quizzes/index.blade.php`)
- **Purpose**: Manage all quizzes
- **Features**: View quizzes, filter by subject/class
- **Path**: `/pages/Quizzes/index.blade.php`

### 2. Create Quiz (`Quizzes/create.blade.php`)
- **Purpose**: Create new quizzes
- **Features**: Quiz creation form
- **Path**: `/pages/Quizzes/create.blade.php`

### 3. Edit Quiz (`Quizzes/edit.blade.php`)
- **Purpose**: Update existing quizzes
- **Features**: Quiz editing interface
- **Path**: `/pages/Quizzes/edit.blade.php`

---

## Receipt

### 1. Receipt Index (`Receipt/index.blade.php`)
- **Purpose**: View payment receipts
- **Features**: Receipt history, search and filter
- **Path**: `/pages/Receipt/index.blade.php`

### 2. Add Receipt (`Receipt/add.blade.php`)
- **Purpose**: Generate new receipts
- **Features**: Receipt creation form
- **Path**: `/pages/Receipt/add.blade.php`

### 3. Edit Receipt (`Receipt/edit.blade.php`)
- **Purpose**: Update receipt information
- **Features**: Receipt modification interface
- **Path**: `/pages/Receipt/edit.blade.php`

### 4. Delete Receipt (`Receipt/Delete.blade.php`)
- **Purpose**: Remove receipts
- **Features**: Receipt deletion confirmation
- **Path**: `/pages/Receipt/Delete.blade.php`

---

## Sections

### 1. Sections Management (`Sections/Sections.blade.php`)
- **Purpose**: Manage class sections
- **Features**: CRUD operations for sections
- **Path**: `/pages/Sections/Sections.blade.php`

---

## Students

### Main Student Management

### 1. Students Index (`Students/index.blade.php`)
- **Purpose**: Main student directory
- **Features**: Student list, search, filter by grade/class
- **Path**: `/pages/Students/index.blade.php`

### 2. Add Student (`Students/add.blade.php`)
- **Purpose**: Register new students
- **Features**: Student registration form
- **Path**: `/pages/Students/add.blade.php`

### 3. Delete Student (`Students/Delete.blade.php`)
- **Purpose**: Remove student records
- **Features**: Student deletion confirmation
- **Path**: `/pages/Students/Delete.blade.php`

### 4. Delete Student Image (`Students/Delete_img.blade.php`)
- **Purpose**: Remove student photos
- **Features**: Image deletion interface
- **Path**: `/pages/Students/Delete_img.blade.php`

### Graduated Students

### 5. Graduated Students Index (`Students/Graduated/index.blade.php`)
- **Purpose**: View graduated students
- **Features**: Alumni list, search functionality
- **Path**: `/pages/Students/Graduated/index.blade.php`

### 6. Create Graduated Student (`Students/Graduated/create.blade.php`)
- **Purpose**: Graduate students
- **Features**: Graduation process interface
- **Path**: `/pages/Students/Graduated/create.blade.php`

### 7. Delete Graduated Student (`Students/Graduated/Delete.blade.php`)
- **Purpose**: Remove graduated records
- **Features**: Graduated student deletion
- **Path**: `/pages/Students/Graduated/Delete.blade.php`

### 8. Return Graduated Student (`Students/Graduated/return.blade.php`)
- **Purpose**: Re-enroll graduated students
- **Features**: Student re-enrollment interface
- **Path**: `/pages/Students/Graduated/return.blade.php`

### Promotions

### 9. Promotions Index (`Students/Promotion/index.blade.php`)
- **Purpose**: Student promotion management
- **Features**: View and manage student promotions
- **Path**: `/pages/Students/Promotion/index.blade.php`

### 10. Create Promotion (`Students/Promotion/create.blade.php`)
- **Purpose**: Promote students to next grade
- **Features**: Promotion creation interface
- **Path**: `/pages/Students/Promotion/create.blade.php`

### 11. Delete Promotion (`Students/Promotion/Delete.blade.php`)
- **Purpose**: Cancel promotions
- **Features**: Promotion deletion
- **Path**: `/pages/Students/Promotion/Delete.blade.php`

### Additional Student Screens

### 12. Students Dashboard (`Students/dashboard/exams/index.blade.php`)
- **Purpose**: Student academic dashboard
- **Features**: Exam results, performance metrics
- **Path**: `/pages/Students/dashboard/exams/index.blade.php`

### 13. Edit Student (`Students/edit.blade.php`)
- **Purpose**: Update student information
- **Features**: Student profile editing
- **Path**: `/pages/Students/edit.blade.php`

### 14. Student Classes (`Students/My_Classes.blade.php`)
- **Purpose**: View student's class assignments
- **Features**: Class schedule and information
- **Path**: `/pages/Students/My_Classes.blade.php`

### 15. Student Profile (`Students/profile.blade.php`)
- **Purpose**: View student profile
- **Features**: Detailed student information
- **Path**: `/pages/Students/profile.blade.php`

### 16. Upload Student (`Students/Upload.blade.php`)
- **Purpose**: Bulk student upload
- **Features**: CSV/Excel import functionality
- **Path**: `/pages/Students/Upload.blade.php`

### 17. Upload Attachment (`Students/Upload_attachment.blade.php`)
- **Purpose**: Upload student documents
- **Features**: File upload for student records
- **Path**: `/pages/Students/Upload_attachment.blade.php`

### 18. Zoom Classes (`Students/Zoom.blade.php`)
- **Purpose**: Student online classes
- **Features**: Virtual classroom access
- **Path**: `/pages/Students/Zoom.blade.php`

---

## Subjects

### 1. Subjects Index (`Subjects/index.blade.php`)
- **Purpose**: Manage subjects
- **Features**: Subject list, CRUD operations
- **Path**: `/pages/Subjects/index.blade.php`

### 2. Create Subject (`Subjects/create.blade.php`)
- **Purpose**: Add new subjects
- **Features**: Subject creation form
- **Path**: `/pages/Subjects/create.blade.php`

### 3. Edit Subject (`Subjects/edit.blade.php`)
- **Purpose**: Update subject information
- **Features**: Subject editing interface
- **Path**: `/pages/Subjects/edit.blade.php`

---

## Teachers

### 1. Teachers Index (`Teachers/index.blade.php`)
- **Purpose**: Main teacher directory
- **Features**: Teacher list, search, filter
- **Path**: `/pages/Teachers/index.blade.php`

### 2. Add Teacher (`Teachers/add.blade.php`)
- **Purpose**: Hire new teachers
- **Features**: Teacher registration form
- **Path**: `/pages/Teachers/add.blade.php`

### 3. Edit Teacher (`Teachers/edit.blade.php`)
- **Purpose**: Update teacher information
- **Features**: Teacher profile editing
- **Path**: `/pages/Teachers/edit.blade.php`

### 4. Delete Teacher (`Teachers/Delete.blade.php`)
- **Purpose**: Remove teacher records
- **Features**: Teacher deletion confirmation
- **Path**: `/pages/Teachers/Delete.blade.php`

### 5. Teacher Dashboard (`Teachers/dashboard.blade.php`)
- **Purpose**: Teacher main dashboard
- **Features**: Overview of teacher's activities
- **Path**: `/pages/Teachers/dashboard.blade.php`

### 6. Teacher Exams (`Teachers/dashboard/exams.blade.php`)
- **Purpose**: Teacher exam management
- **Features:": Exam creation and management
- **Path**: `/pages/Teachers/dashboard/exams.blade.php`

### 7. Teacher Library (`Teachers/dashboard/library.blade.php`)
- **Purpose**: Teacher library resources
- **Features**: Resource management
- **Path**: `/pages/Teachers/dashboard/library.blade.php`

### 8. Teacher Online Classes (`Teachers/dashboard/online_classes.blade.php`)
- **Purpose**: Teacher virtual classes
- **Features**: Online class management
- **Path**: `/pages/Teachers/dashboard/online_classes.blade.php`

### 9. Teacher Profile (`Teachers/profile.blade.php`)
- **Purpose**: Teacher profile view
- **Features**: Detailed teacher information
- **Path**: `/pages/Teachers/profile.blade.php`

### 10. Teacher Questions (`Teachers/dashboard/questions.blade.php`)
- **Purpose**: Teacher question bank
- **Features**: Question management
- **Path**: `/pages/Teachers/dashboard/questions.blade.php`

### 11. Teacher Quizzes (`Teachers/dashboard/quizzes.blade.php`)
- **Purpose**: Teacher quiz management
- **Features**: Quiz creation and grading
- **Path**: `/pages/Teachers/dashboard/quizzes.blade.php`

### 12. Teacher Students (`Teachers/dashboard/students.blade.php`)
- **Purpose**: Teacher's students
- **Features**: Student list and management
- **Path**: `/pages/Teachers/dashboard/students.blade.php`

### 13. Teacher Subjects (`Teachers/dashboard/subjects.blade.php`)
- **Purpose**: Teacher's subjects
- **Features**: Subject assignment and management
- **Path**: `/pages/Teachers/dashboard/subjects.blade.php`

### 14. Upload Teacher (`Teachers/Upload.blade.php`)
- **Purpose**: Bulk teacher upload
- **Features**: CSV/Excel import for teachers
- **Path**: `/pages/Teachers/Upload.blade.php`

### 15. Upload Teacher Attachment (`Teachers/Upload_attachment.blade.php`)
- **Purpose**: Upload teacher documents
- **Features**: File upload for teacher records
- **Path**: `/pages/Teachers/Upload_attachment.blade.php`

### 16. Delete Teacher Image (`Teachers/Delete_img.blade.php`)
- **Purpose**: Remove teacher photos
- **Features**: Teacher image deletion
- **Path**: `/pages/Teachers/Delete_img.blade.php`

### 17. Teacher Attendance (`Teachers/Attendance.blade.php`)
- **Purpose**: Teacher attendance management
- **Features**: Attendance tracking
- **Path**: `/pages/Teachers/Attendance.blade.php`

### 18. Teacher Library Main (`Teachers/library.blade.php`)
- **Purpose**: Teacher library main page
- **Features**: Library resource access
- **Path**: `/pages/Teachers/library.blade.php`

### 19. Teacher Online Classes Main (`Teachers/online_classes.blade.php`)
- **Purpose**: Teacher online classes main
- **Features**: Virtual class overview
- **Path**: `/pages/Teachers/online_classes.blade.php`

### 20. Teacher Quizzes Main (`Teachers/quizzes.blade.php`)
- **Purpose**: Teacher quizzes main page
- **Features**: Quiz overview and management
- **Path**: `/pages/Teachers/quizzes.blade.php`

### 21. Teacher Subjects Main (`Teachers/subjects.blade.php`)
- **Purpose**: Teacher subjects main page
- **Features**: Subject overview
- **Path**: `/pages/Teachers/subjects.blade.php`

---

## Library

### 1. Library Index (`library/index.blade.php`)
- **Purpose**: Main library interface
- **Features**: Browse library resources
- **Path**: `/pages/library/index.blade.php`

### 2. Create Library Item (`library/create.blade.php`)
- **Purpose**: Add new library resources
- **Features**: Resource upload form
- **Path**: `/pages/library/create.blade.php`

### 3. Edit Library Item (`library/edit.blade.php`)
- **Purpose**: Update library resources
- **Features**: Resource editing interface
- **Path**: `/pages/library/edit.blade.php`

### 4. Delete Library Item (`library/Delete.blade.php`)
- **Purpose**: Remove library resources
- **Features**: Resource deletion confirmation
- **Path**: `/pages/library/Delete.blade.php`

---

## Online Classes

### 1. Online Classes Index (`online_classes/index.blade.php`)
- **Purpose**: Virtual classroom management
- **Features**: List all online classes
- **Path**: `/pages/online_classes/index.blade.php`

### 2. Create Online Class (`online_classes/create.blade.php`)
- **Purpose**: Schedule new online classes
- **Features**: Online class creation form
- **Path**: `/pages/online_classes/create.blade.php`

### 3. Edit Online Class (`online_classes/edit.blade.php`)
- **Purpose**: Update online class details
- **Features**: Online class editing
- **Path**: `/pages/online_classes/edit.blade.php`

### 4. Delete Online Class (`online_classes/Delete.blade.php`)
- **Purpose**: Cancel online classes
- **Features**: Online class deletion
- **Path**: `/pages/online_classes/Delete.blade.php`

---

## Parents

### 1. Parents Index (`parents/index.blade.php`)
- **Purpose**: Parent directory
- **Features**: Parent list and search
- **Path**: `/pages/parents/index.blade.php`

### 2. Add Parent (`parents/add.blade.php`)
- **Purpose**: Register new parents
- **Features**: Parent registration form
- **Path**: `/pages/parents/add.blade.php`

### 3. Edit Parent (`parents/edit.blade.php`)
- **Purpose**: Update parent information
- **Features**: Parent profile editing
- **Path**: `/pages/parents/edit.blade.php`

### 4. Delete Parent (`parents/Delete.blade.php`)
- **Purpose**: Remove parent records
- **Features**: Parent deletion confirmation
- **Path**: `/pages/parents/Delete.blade.php`

### 5. Parent Dashboard (`parents/dashboard.blade.php`)
- **Purpose**: Parent main dashboard
- **Features**: Overview of children's activities
- **Path**: `/pages/parents/dashboard.blade.php`

### 6. Parent Exams (`parents/dashboard/exams.blade.php`)
- **Purpose**: View children's exam results
- **Features**: Exam results and performance
- **Path**: `/pages/parents/dashboard/exams.blade.php`

### 7. Parent Fees (`parents/dashboard/fees.blade.php`)
- **Purpose**: View fee status
- **Features**: Fee payments and invoices
- **Path**: `/pages/parents/dashboard/fees.blade.php`

---

## Settings

### 1. Settings Management (`setting/setting.blade.php`)
- **Purpose**: System configuration
- **Features**: School settings and preferences
- **Path**: `/pages/setting/setting.blade.php`

---

## Summary

**Total Screens**: 41 screens across 18 modules

**Module Breakdown**:
- Attendance: 2 screens
- Fees: 4 screens
- Fees Invoices: 4 screens
- Grades: 1 screen
- My Classes: 1 screen
- Payment: 4 screens
- Processing Fee: 4 screens
- Questions: 4 screens
- Quizzes: 3 screens
- Receipt: 4 screens
- Sections: 1 screen
- Students: 18 screens (largest module)
- Subjects: 3 screens
- Teachers: 21 screens (most comprehensive module)
- Library: 4 screens
- Online Classes: 4 screens
- Parents: 7 screens
- Settings: 1 screen

Each screen follows Laravel Blade templating standards and is designed to provide a comprehensive school management experience with full CRUD operations for all major entities.
