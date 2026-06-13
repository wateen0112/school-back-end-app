# Blade Pages Report

Generated for the Laravel school management project in `resources/views`.

This report covers all 125 Blade files found in the project. For each file it describes the page or partial goal, the visible content, and the main workflow/actions detected from the Blade markup.

## Summary

- Auth and public views: login, role selection, registration, password reset, email verification.
- Shared layouts: app shell, master dashboard shell, header, footer, sidebars, menu partials, scripts, CSS head.
- Livewire widgets: parent wizard, father/mother forms, parent table, calendars, quiz question display.
- Admin pages: dashboard, grades, classrooms, sections, students, promotions, graduation, teachers, parents, fees, invoices, receipts, payments, attendance, subjects, quizzes, questions, library, online classes, settings.
- Parent portal pages: children, degrees, attendance, fees, receipts, profile.
- Student portal pages: dashboard, exams, exam display, profile.
- Teacher portal pages: dashboard, sections, students, attendance reports, quizzes, questions, online classes, profile.

## Global Layout And Shared Views

### `resources/views/layouts/master.blade.php`
Goal: Main authenticated dashboard layout for admin, teacher, student, and parent pages.

Content and workflow: Defines the full page frame with preloader, wrapper, sidebar, header, breadcrumb area, content yield, footer, CSS and JS yields. It chooses the correct sidebar based on `auth()` guard: student, teacher, parent, or admin. Most management pages extend this layout.

### `resources/views/layouts/app.blade.php`
Goal: Public Laravel auth layout.

Content and workflow: Provides a Bootstrap-style public shell with navbar links to login/register, logout form when authenticated, and `@yield('content')`. Used by default Laravel auth password/register/verify views.

### `resources/views/layouts/head.blade.php`
Goal: Shared HTML head partial.

Content and workflow: Loads meta tags, favicon, plugin CSS, LTR/RTL styles based on locale, app CSS, and design-system CSS. It is responsible for global visual dependencies.

### `resources/views/layouts/footer.blade.php`
Goal: Dashboard footer partial.

Content and workflow: Provides footer markup displayed inside the authenticated dashboard layout.

### `resources/views/layouts/footer-scripts.blade.php`
Goal: Shared JavaScript dependency partial.

Content and workflow: Loads jQuery, Bootstrap, plugins, validation, DataTables, charts, calendars, Toastr, SweetAlert, and custom scripts used across management pages.

### `resources/views/layouts/main-header.blade.php`
Goal: Top navigation bar for authenticated users.

Content and workflow: Contains search, fullscreen, notifications, quick app dropdown, user avatar dropdown, profile links, and guard-aware logout forms. The logout workflow submits the route for student, teacher, parent, or web guard.

### `resources/views/layouts/main-sidebar.blade.php`
Goal: Legacy generic sidebar placeholder.

Content and workflow: Shared sidebar area retained for layout compatibility.

### `resources/views/components/sidebar/shell.blade.php`
Goal: Reusable sidebar wrapper component.

Content and workflow: Wraps a dashboard URL, logo/home area, and injected menu slot. Used by role-specific sidebars.

### `resources/views/layouts/main-sidebar/admin-main-sidebar.blade.php`
Goal: Admin sidebar wrapper.

Content and workflow: Loads the reusable sidebar shell and injects the admin menu partial.

### `resources/views/layouts/main-sidebar/teacher-main-sidebar.blade.php`
Goal: Teacher sidebar wrapper.

Content and workflow: Loads the reusable sidebar shell and injects the teacher menu partial.

### `resources/views/layouts/main-sidebar/student-main-sidebar.blade.php`
Goal: Student sidebar wrapper.

Content and workflow: Loads the reusable sidebar shell with the student dashboard route and student menu.

### `resources/views/layouts/main-sidebar/parent-main-sidebar.blade.php`
Goal: Parent sidebar wrapper.

Content and workflow: Loads the reusable sidebar shell with the parent dashboard route and parent menu.

### `resources/views/layouts/main-sidebar/menus/admin.blade.php`
Goal: Admin navigation menu.

Content and workflow: Provides collapsible navigation groups for grades, classrooms, sections, students, promotions, graduated students, teachers, parents, accounts, attendance, subjects, exams, library, online classes, settings, and icon demo links. Links point to CRUD/index routes such as `Grades.index`, `Students.create`, `Fees.index`, `Quizzes.index`, and `settings.index`.

### `resources/views/layouts/main-sidebar/menus/teacher.blade.php`
Goal: Teacher navigation menu.

Content and workflow: Links teachers to sections, students, quizzes, questions, online classes, attendance reports, exams report placeholder, and teacher profile.

### `resources/views/layouts/main-sidebar/menus/student.blade.php`
Goal: Student navigation menu.

Content and workflow: Links students to available exams and profile.

### `resources/views/layouts/main-sidebar/menus/parent.blade.php`
Goal: Parent navigation menu.

Content and workflow: Links parents to children list, attendance report, financial report, and profile.

### `resources/views/empty.blade.php`
Goal: Blank scaffold page.

Content and workflow: Extends the master layout with placeholder title, breadcrumb, content area, CSS, and JS sections. Useful as a template for new dashboard pages.

### `resources/views/test_pg.blade.php`
Goal: Test scaffold page.

Content and workflow: Mirrors the empty layout structure for testing page layout, breadcrumb, CSS, and JS injection.

### `resources/views/home.blade.php`
Goal: Default post-auth Laravel home page.

Content and workflow: Extends the public app layout and displays the default authenticated content area.

## Authentication And Public Entry Pages

### `resources/views/auth/selection.blade.php`
Goal: Login role selection page.

Content and workflow: Presents selectable login cards/options for different user types and routes the user to `login.show` with the selected type.

### `resources/views/auth/login.blade.php`
Goal: Login form for a selected user type.

Content and workflow: Contains a styled login screen with email, password, hidden `type`, remember checkbox, forgot-password link, and submit button. Posts to `login`. It also includes footer policy links.

### `resources/views/auth/register.blade.php`
Goal: User registration page.

Content and workflow: Provides name, email, password, and password confirmation fields, then posts to `register`. Displays validation errors and uses the public auth layout.

### `resources/views/auth/passwords/email.blade.php`
Goal: Password reset email request page.

Content and workflow: Shows status messages, email input, validation errors, and submit button. Posts to `password.email`.

### `resources/views/auth/passwords/reset.blade.php`
Goal: Password reset form.

Content and workflow: Accepts reset token, email, new password, and confirmation, then posts to `password.update`.

### `resources/views/auth/passwords/confirm.blade.php`
Goal: Password confirmation page.

Content and workflow: Requests the current password before sensitive actions, posts to `password.confirm`, and provides a password-request link.

### `resources/views/auth/verify.blade.php`
Goal: Email verification notice page.

Content and workflow: Informs the user to verify their email and includes a resend verification form posting to `verification.resend`.

## Dashboard Pages

### `resources/views/dashboard.blade.php`
Goal: Admin dashboard overview.

Content and workflow: Displays stat cards for students, teachers, parents, and sections with links to related modules. Includes tabbed data tables for students, teachers, parents, and fee invoices. Goal is to provide a high-level administrative snapshot and shortcuts to key records.

### `resources/views/pages/Students/dashboard.blade.php`
Goal: Student dashboard landing page.

Content and workflow: Student-specific overview page using the dashboard layout. It is a simple portal landing surface for student modules.

### `resources/views/pages/parents/dashboard.blade.php`
Goal: Parent dashboard landing page.

Content and workflow: Parent portal overview page. It acts as the entry point for parent-only child, attendance, fees, and profile modules.

### `resources/views/pages/Teachers/dashboard/dashboard.blade.php`
Goal: Teacher dashboard overview.

Content and workflow: Displays teacher-specific stat cards and four tables/sections for teacher data. Links to teacher student and section workflows. Goal is to give teachers fast access to assigned students, classes, and teaching tasks.

## Livewire Components

### `resources/views/livewire/add-parent.blade.php`
Goal: Multi-step parent creation wizard.

Content and workflow: Renders the Livewire add-parent workflow with step navigation and two hidden/input controls. Coordinates father form, mother form, and parent table components.

### `resources/views/livewire/Father_Form.blade.php`
Goal: Father data step for parent creation.

Content and workflow: Captures father name in Arabic/English, job, national ID, passport ID, phone, nationality, blood type, religion, email, password, and address data through Livewire-bound controls.

### `resources/views/livewire/Mother_Form.blade.php`
Goal: Mother data step for parent creation.

Content and workflow: Captures mother name in Arabic/English, job, national ID, passport ID, phone, nationality, blood type, religion, and address through Livewire-bound controls.

### `resources/views/livewire/Parent_Table.blade.php`
Goal: Parent list table in the Livewire parent workflow.

Content and workflow: Displays existing parent records in a table with father/mother information and action columns.

### `resources/views/livewire/calendar.blade.php`
Goal: Admin calendar widget.

Content and workflow: Renders a calendar area for school events or scheduling inside dashboard pages.

### `resources/views/livewire/calendar-student.blade.php`
Goal: Student calendar widget.

Content and workflow: Renders a student-facing calendar area for student schedule/events.

### `resources/views/livewire/show_Form.blade.php`
Goal: Livewire wrapper/test page.

Content and workflow: Extends master layout and provides a standard page scaffold for rendering Livewire form content.

### `resources/views/livewire/show-question.blade.php`
Goal: Student quiz question interaction component.

Content and workflow: Displays one quiz question with answer input/selection and Livewire state handling for exam participation.

## Academic Structure: Grades, Classrooms, Sections

### `resources/views/pages/Grades/Grades.blade.php`
Goal: Manage grade/stage records.

Content and workflow: Shows a DataTable of grades with name and notes. Includes add, edit, and delete Bootstrap modals. Forms post to `Grades.store`, `Grades.update`, and `Grades.destroy`. Goal is full CRUD for school grade stages.

### `resources/views/pages/My_Classes/My_Classes.blade.php`
Goal: Manage classroom records.

Content and workflow: Displays classrooms in a DataTable with grade association. Includes filter form by grade, bulk delete form, add modal, edit modal, and delete modal. Posts to `Classrooms.store`, `Classrooms.update`, `Classrooms.destroy`, `delete_all`, and `Filter_Classes`.

### `resources/views/pages/Sections/Sections.blade.php`
Goal: Manage sections and teacher assignments.

Content and workflow: Organizes sections inside grade accordions. Each section row displays section name, classroom, status, and operations. Includes add/edit/delete modals with Arabic/English section names, grade, class, status, and multiple teacher selection. Uses AJAX to load classrooms when grade changes.

### `resources/views/pages/Attendance/Sections.blade.php`
Goal: Choose a section for attendance.

Content and workflow: Shows sections grouped by grade in an accordion and provides a "students list" action to `Attendance.show`. Goal is to navigate from section selection to attendance marking for that section.

## Student Management

### `resources/views/pages/Students/index.blade.php`
Goal: List and manage students.

Content and workflow: DataTable of students with name, email, gender, grade, classroom, section, and action dropdown. Actions include view student, edit, add fee invoice, receipt, processing fee, payment voucher, and delete. Includes delete modal partials per student.

### `resources/views/pages/Students/add.blade.php`
Goal: Create a student.

Content and workflow: Multi-section form for personal information, student academic information, parent assignment, academic year, and photo attachments. Fields include Arabic/English name, email, password, gender, nationality, blood type, birth date, grade, classroom, section, parent, academic year, and uploaded photos. Posts to `Students.store`.

### `resources/views/pages/Students/edit.blade.php`
Goal: Edit a student.

Content and workflow: Same student fields as add page, pre-filled from the existing student. Supports changing personal data, academic placement, parent, year, and attachments. Posts to `Students.update`.

### `resources/views/pages/Students/show.blade.php`
Goal: Display student profile details and attachments.

Content and workflow: Uses tabs/tables to show student personal and academic data, attachment list, and upload form. Provides a form to upload attachments via `Upload_attachment` and includes delete image modal partials.

### `resources/views/pages/Students/Delete.blade.php`
Goal: Confirm student deletion.

Content and workflow: Bootstrap modal with hidden student id, readonly student name, cancel button, and danger submit button. Posts to `Students.destroy`.

### `resources/views/pages/Students/Delete_img.blade.php`
Goal: Confirm deletion of a student attachment.

Content and workflow: Bootstrap modal with hidden image id, student name, file name, and delete form posting to `Delete_attachment`.

### `resources/views/pages/Students/promotion/index.blade.php`
Goal: Promote students from one class/section/year to another.

Content and workflow: Form captures current grade, classroom, section, academic year, target grade, target classroom, target section, and target academic year. Posts to `Promotion.store`. Uses dependent dropdowns for class and section selection.

### `resources/views/pages/Students/promotion/management.blade.php`
Goal: Review and manage promoted students.

Content and workflow: DataTable of promotions showing student, old and new grade/class/section/year values. Includes buttons to revert one student, revert all, and a placeholder graduation action.

### `resources/views/pages/Students/promotion/Delete_one.blade.php`
Goal: Revert one student promotion.

Content and workflow: Confirmation modal posting to `Promotion.destroy` with a single promotion/student id.

### `resources/views/pages/Students/promotion/Delete_all.blade.php`
Goal: Revert all promotions.

Content and workflow: Confirmation modal posting to `Promotion.destroy` for bulk promotion rollback.

### `resources/views/pages/Students/Graduated/create.blade.php`
Goal: Mark students as graduated.

Content and workflow: Form selects grade, classroom, and section to graduate matching students. Posts to `Graduated.store`.

### `resources/views/pages/Students/Graduated/index.blade.php`
Goal: List graduated students.

Content and workflow: DataTable of graduated students with academic information and action buttons. Includes modal partials for returning or deleting graduated records.

### `resources/views/pages/Students/Graduated/Delete.blade.php`
Goal: Delete a graduated student record.

Content and workflow: Confirmation modal with hidden id and readonly student name. Posts to `Graduated.destroy`.

### `resources/views/pages/Students/Graduated/return.blade.php`
Goal: Return a graduated student to active status.

Content and workflow: Confirmation modal posting to `Graduated.update`; shows student name and submit/cancel buttons.

## Attendance

### `resources/views/pages/Attendance/index.blade.php`
Goal: Mark daily attendance for students in a selected section.

Content and workflow: Displays current date, a table of students, and present/absent radio buttons per student. Hidden fields carry student ids, grade id, classroom id, and section id. Posts attendance data to `Attendance.store`. Already-recorded attendance disables inputs.

### `resources/views/pages/Teachers/dashboard/students/index.blade.php`
Goal: Teacher view of assigned students for attendance.

Content and workflow: Displays students in a table and posts to the `attendance` route to open/record attendance for selected students or sections. It is teacher-scoped compared with the admin attendance page.

### `resources/views/pages/Teachers/dashboard/students/attendance_report.blade.php`
Goal: Teacher attendance report search.

Content and workflow: Provides date range filters and student selection, then displays a table of attendance records. Posts search criteria to `attendance.search`.

### `resources/views/pages/Teachers/dashboard/students/edit_attendance.blade.php`
Goal: Edit an attendance entry.

Content and workflow: Bootstrap modal with attendance status controls and hidden attendance/student data. Posts to `attendance.edit`.

### `resources/views/pages/parents/Attendance/index.blade.php`
Goal: Parent attendance report.

Content and workflow: Allows parent to filter child attendance by date range and child. Shows a table with attendance results. Posts to `sons.attendance.search`.

## Teachers

### `resources/views/pages/Teachers/Teachers.blade.php`
Goal: List and manage teachers.

Content and workflow: DataTable with teacher name, gender, joining date, specialization, and operation buttons. Supports create, edit, and delete via `Teachers.create`, `Teachers.edit`, and `Teachers.destroy`. Includes delete modal.

### `resources/views/pages/Teachers/create.blade.php`
Goal: Create teacher account/profile.

Content and workflow: Form captures email, password, Arabic/English name, specialization, gender, joining date, and address. Posts to `Teachers.store`.

### `resources/views/pages/Teachers/Edit.blade.php`
Goal: Edit teacher account/profile.

Content and workflow: Pre-filled form for email, password, Arabic/English name, specialization, gender, joining date, and address. Posts to `Teachers.update`.

### `resources/views/pages/Teachers/dashboard/profile.blade.php`
Goal: Teacher profile update page.

Content and workflow: Form for teacher name, password, and related profile fields. Posts to `profile.update`.

### `resources/views/pages/Teachers/dashboard/sections/index.blade.php`
Goal: Teacher assigned sections list.

Content and workflow: Displays teacher-related sections/classes in a DataTable so the teacher can see assigned teaching groups.

## Parent Portal

### `resources/views/pages/parents/children/index.blade.php`
Goal: Parent children list.

Content and workflow: Shows a table of children linked to the authenticated parent. Provides a route to child results through `sons.results`.

### `resources/views/pages/parents/degrees/index.blade.php`
Goal: Parent child grades/results page.

Content and workflow: Displays child degree/exam result records in a table for parent review.

### `resources/views/pages/parents/fees/index.blade.php`
Goal: Parent financial report.

Content and workflow: Displays child fee invoices/financial obligations in a table. Includes receipt navigation through `sons.receipt`.

### `resources/views/pages/parents/Receipt/index.blade.php`
Goal: Parent receipt list.

Content and workflow: Displays receipt/payment records for children in a table.

### `resources/views/pages/parents/profile.blade.php`
Goal: Parent profile update page.

Content and workflow: Form updates parent account data such as name, password, phone/profile fields. Posts to `profile.update.parent`.

## Student Portal

### `resources/views/pages/Students/dashboard/exams/index.blade.php`
Goal: Student available exams list.

Content and workflow: Displays quizzes available to the authenticated student in a table. Links to `student_exams.show` to start/view an exam.

### `resources/views/pages/Students/dashboard/exams/show.blade.php`
Goal: Student exam taking page.

Content and workflow: Shows the selected quiz/exam interface, typically backed by the Livewire question component. Goal is to let students answer questions and submit exam responses.

### `resources/views/pages/Students/dashboard/profile.blade.php`
Goal: Student profile update page.

Content and workflow: Form for student profile/password fields. Posts to `profile-student.update`.

## Fees And Financial Management

### `resources/views/pages/Fees/index.blade.php`
Goal: Course/fee registration landing form.

Content and workflow: This page is not a normal fee table. It presents a standalone subscription/registration-style form with status/error display and posts to `confirmation.Register`. It includes six fields and a closed-registration style message.

### `resources/views/pages/Fees/add.blade.php`
Goal: Create a tuition/bus fee.

Content and workflow: Form captures Arabic name, English name, amount, grade, classroom, academic year, fee type, and notes. Posts to `Fees.store`.

### `resources/views/pages/Fees/edit.blade.php`
Goal: Edit a fee.

Content and workflow: Pre-filled version of the add fee form with amount, translations, grade, classroom, year, fee type, and notes. Posts to `Fees.update`.

### `resources/views/pages/Fees/Delete.blade.php`
Goal: Delete a fee.

Content and workflow: Confirmation modal with hidden id and danger submit. Posts to `Fees.destroy`.

### `resources/views/pages/Fees_Invoices/index.blade.php`
Goal: List fee invoices.

Content and workflow: DataTable listing student fee invoices and links to edit invoice records through `Fees_Invoices.edit`.

### `resources/views/pages/Fees_Invoices/add.blade.php`
Goal: Create fee invoice for a student.

Content and workflow: Form selects student/fee data and captures invoice amount/description fields. Posts to `Fees_Invoices.store`.

### `resources/views/pages/Fees_Invoices/edit.blade.php`
Goal: Edit fee invoice.

Content and workflow: Pre-filled invoice form with student, fee, amount, and description style fields. Posts to `Fees_Invoices.update`.

### `resources/views/pages/Fees_Invoices/Delete.blade.php`
Goal: Delete fee invoice.

Content and workflow: Confirmation modal posting to `Fees_Invoices.destroy`.

### `resources/views/pages/Receipt/index.blade.php`
Goal: List student receipt vouchers.

Content and workflow: DataTable of receipt records and edit links through `receipt_students.edit`.

### `resources/views/pages/Receipt/add.blade.php`
Goal: Create receipt voucher.

Content and workflow: Form captures student, amount/debit value, and description. Posts to `receipt_students.store`.

### `resources/views/pages/Receipt/edit.blade.php`
Goal: Edit receipt voucher.

Content and workflow: Pre-filled receipt form with student, amount, and description fields. Posts to `receipt_students.update`.

### `resources/views/pages/Receipt/Delete.blade.php`
Goal: Delete receipt voucher.

Content and workflow: Confirmation modal posting to `receipt_students.destroy`.

### `resources/views/pages/ProcessingFee/index.blade.php`
Goal: List fee exclusions/processing fees.

Content and workflow: DataTable of processing-fee records and edit links through `ProcessingFee.edit`.

### `resources/views/pages/ProcessingFee/add.blade.php`
Goal: Create fee exclusion/processing fee.

Content and workflow: Form captures student, amount, and description. Posts to `ProcessingFee.store`.

### `resources/views/pages/ProcessingFee/edit.blade.php`
Goal: Edit processing fee.

Content and workflow: Pre-filled form for student, amount, and description. Posts to `ProcessingFee.update`.

### `resources/views/pages/ProcessingFee/Delete.blade.php`
Goal: Delete processing fee.

Content and workflow: Confirmation modal posting to `ProcessingFee.destroy`.

### `resources/views/pages/Payment/index.blade.php`
Goal: List payment vouchers.

Content and workflow: DataTable of payment voucher records and edit links through `Payment_students.edit`.

### `resources/views/pages/Payment/add.blade.php`
Goal: Create payment voucher.

Content and workflow: Form captures student, amount, and description. Posts to `Payment_students.store`.

### `resources/views/pages/Payment/edit.blade.php`
Goal: Edit payment voucher.

Content and workflow: Pre-filled payment form with student, amount, and description. Posts to `Payment_students.update`.

### `resources/views/pages/Payment/Delete.blade.php`
Goal: Delete payment voucher.

Content and workflow: Confirmation modal posting to `Payment_students.destroy`.

## Subjects, Quizzes, And Questions

### `resources/views/pages/Subjects/index.blade.php`
Goal: List and manage subjects.

Content and workflow: DataTable of subjects with class, grade, and teacher information. Includes create, edit, and delete actions with delete modal posting to `subjects.destroy`.

### `resources/views/pages/Subjects/create.blade.php`
Goal: Create subject.

Content and workflow: Form captures Arabic/English subject name, grade, classroom, and teacher. Posts to `subjects.store`.

### `resources/views/pages/Subjects/edit.blade.php`
Goal: Edit subject.

Content and workflow: Pre-filled subject form with name translations, grade, classroom, and teacher. Posts to `subjects.update`.

### `resources/views/pages/Quizzes/index.blade.php`
Goal: Admin quiz list and management.

Content and workflow: DataTable of quizzes with create, edit, and delete actions. Includes delete modal/form posting to `Quizzes.destroy`.

### `resources/views/pages/Quizzes/create.blade.php`
Goal: Create quiz.

Content and workflow: Form captures quiz name, subject, grade, classroom, section/teacher-related selections, and notes. Posts to `Quizzes.store`.

### `resources/views/pages/Quizzes/edit.blade.php`
Goal: Edit quiz.

Content and workflow: Pre-filled quiz form with quiz name and related subject/class fields. Posts to `Quizzes.update`.

### `resources/views/pages/Questions/index.blade.php`
Goal: Admin question list and management.

Content and workflow: DataTable of quiz questions with create and edit links. Delete behavior is handled through the destroy modal partial.

### `resources/views/pages/Questions/create.blade.php`
Goal: Create quiz question.

Content and workflow: Form captures question title/text, answer choices, correct answer, score/grade, and quiz association. Posts to `questions.store`.

### `resources/views/pages/Questions/edit.blade.php`
Goal: Edit quiz question.

Content and workflow: Pre-filled question form with quiz, question text, answers, correct answer, and score fields. Posts to `questions.update`.

### `resources/views/pages/Questions/destroy.blade.php`
Goal: Delete quiz question.

Content and workflow: Confirmation modal posting to `questions.destroy`.

## Teacher Quiz And Question Pages

### `resources/views/pages/Teachers/dashboard/Quizzes/index.blade.php`
Goal: Teacher quiz list.

Content and workflow: DataTable of quizzes owned/visible to the teacher with actions to create, edit, show questions, show student quiz attempts, and delete. Includes delete modal posting to `quizzes.destroy`.

### `resources/views/pages/Teachers/dashboard/Quizzes/create.blade.php`
Goal: Teacher creates quiz.

Content and workflow: Form captures quiz name, subject, grade/class/section data, and related metadata. Posts to `quizzes.store`.

### `resources/views/pages/Teachers/dashboard/Quizzes/edit.blade.php`
Goal: Teacher edits quiz.

Content and workflow: Pre-filled teacher quiz form. Posts to `quizzes.update`.

### `resources/views/pages/Teachers/dashboard/Quizzes/student_quizze.blade.php`
Goal: Teacher reviews student quiz attempts.

Content and workflow: Table of student quiz results/attempts with controls to repeat/reset a quiz for a student through `repeat.quizze`. Includes modal confirmation.

### `resources/views/pages/Teachers/dashboard/Questions/index.blade.php`
Goal: Teacher question list.

Content and workflow: DataTable of teacher quiz questions with show and edit actions.

### `resources/views/pages/Teachers/dashboard/Questions/create.blade.php`
Goal: Teacher creates question.

Content and workflow: Form captures question text, choices, correct answer, score, and quiz association. Posts to `questions.store`.

### `resources/views/pages/Teachers/dashboard/Questions/edit.blade.php`
Goal: Teacher edits question.

Content and workflow: Pre-filled question form. Posts to `questions.update`.

### `resources/views/pages/Teachers/dashboard/Questions/destroy.blade.php`
Goal: Teacher deletes question.

Content and workflow: Confirmation modal posting to `questions.destroy`.

## Library

### `resources/views/pages/library/index.blade.php`
Goal: List library books/files.

Content and workflow: DataTable of books with title, grade/class/teacher info, download action through `downloadAttachment`, and edit action through `library.edit`. Includes delete modal partials.

### `resources/views/pages/library/create.blade.php`
Goal: Add library book.

Content and workflow: Form captures book title, grade, classroom, section/teacher association, and file upload. Posts multipart data to `library.store`.

### `resources/views/pages/library/edit.blade.php`
Goal: Edit library book.

Content and workflow: Pre-filled form for title, grade, classroom, teacher/section, and replacement file. Posts to `library.update`.

### `resources/views/pages/library/destroy.blade.php`
Goal: Delete library book.

Content and workflow: Confirmation modal with hidden id and file/title fields, posting to `library.destroy`.

## Online Classes

### `resources/views/pages/online_classes/index.blade.php`
Goal: Admin online Zoom classes list.

Content and workflow: DataTable of online classes with buttons to create direct Zoom class and create indirect class. Includes delete modal partials.

### `resources/views/pages/online_classes/add.blade.php`
Goal: Admin creates direct Zoom class.

Content and workflow: Form selects grade, classroom, section, topic, start datetime, and duration. Posts to `online_classes.store`.

### `resources/views/pages/online_classes/indirect.blade.php`
Goal: Admin creates indirect online class.

Content and workflow: Form selects grade, classroom, section, topic, start datetime, duration, meeting id, password, start URL, and join URL. Posts to `indirect.store.admin`.

### `resources/views/pages/online_classes/delete.blade.php`
Goal: Admin deletes online class.

Content and workflow: Confirmation modal posting to `online_classes.destroy`.

### `resources/views/pages/Teachers/dashboard/online_classes/index.blade.php`
Goal: Teacher online Zoom classes list.

Content and workflow: DataTable of teacher online classes with buttons to create direct or indirect class. Uses `online_zoom_classes.create` and `indirect.teacher.create`.

### `resources/views/pages/Teachers/dashboard/online_classes/add.blade.php`
Goal: Teacher creates direct Zoom class.

Content and workflow: Form selects grade, classroom, section, topic, start datetime, and duration. Posts to `online_zoom_classes.store`.

### `resources/views/pages/Teachers/dashboard/online_classes/indirect.blade.php`
Goal: Teacher creates indirect online class.

Content and workflow: Form captures class placement plus meeting id, password, start URL, and join URL. Posts to `indirect.teacher.store`.

### `resources/views/pages/Teachers/dashboard/online_classes/delete.blade.php`
Goal: Teacher deletes online class.

Content and workflow: Confirmation modal posting to `online_zoom_classes.destroy`.

## Settings

### `resources/views/pages/setting/index.blade.php`
Goal: Edit school/system settings.

Content and workflow: Form captures school name, title, phone, address, current session/year data, logo, and related setting values. Posts to `settings.update`.

## Notes For Mobile App Mapping

- DataTable pages become mobile list screens with search, filter, and row action menus.
- Add/edit Blade forms map directly to mobile create/edit forms.
- Delete modal partials map to mobile confirmation dialogs.
- Grade/class/section dependent dropdown JavaScript should become API-driven cascading selectors.
- Role sidebars define the mobile role navigation: admin has full CMS, teacher has teaching workflow, student has exams/profile, parent has children/reports/profile.
- Livewire parent wizard maps best to a multi-step mobile form: father step, mother step, confirmation/table step.
- Attendance pages require dedicated mobile workflows because they are not simple CRUD: section selection, daily status marking, and report filtering.
