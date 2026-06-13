# Google Stitch Prompt - School Management System Mobile App (Arabic)

## Project Overview
Create a comprehensive Arabic-language mobile app for a school management system using Google Stitch. The app should include all modules with RTL (Right-to-Left) layout support and modern mobile UI patterns.

## Design Requirements

### Language & Direction
- **Primary Language**: Arabic
- **Text Direction**: RTL (Right-to-Left)
- **Font Family**: Cairo or similar Arabic-friendly fonts
- **Numbers**: Arabic-Indic digits preferred

### Color Scheme
- **Primary**: Green (#28a745) - Success actions, confirm buttons
- **Secondary**: Blue (#007bff) - Information, primary actions
- **Danger**: Red (#dc3545) - Delete, warning actions
- **Warning**: Yellow (#ffc107) - Edit, warning states
- **Info**: Light Blue (#9dc8e2) - Financial operations
- **Success**: Golden (#ffd700) - Payment operations

### UI Components
- **Cards**: Material Design cards with shadows
- **Buttons**: Rounded corners, proper spacing
- **Forms**: Floating labels where appropriate
- **Tables**: Responsive with horizontal scroll on mobile
- **Modals**: Full-screen on mobile, dialog on tablet
- **Navigation**: Bottom navigation bar with icons

---

## Screen Specifications

### 1. Login Screen
**Elements:**
- App logo at top
- Email field (البريد الإلكتروني)
- Password field (كلمة المرور)
- Login button (تسجيل الدخول) - Green background
- "Forgot Password" link (نسيت كلمة المرور)
- Language toggle (العربية/English)

### 2. Dashboard/Home Screen
**Header:**
- Welcome message with user name
- Current date in Arabic format
- Notification bell icon

**Quick Stats Cards:**
- Total Students (إجمالي الطلاب)
- Total Teachers (إجمالي المعلمين)
- Today's Attendance (حضور اليوم)
- Pending Fees (الرسوم المعلقة)

**Navigation Grid:**
- Students (الطلاب) - Icon: graduation cap
- Teachers (المعلمون) - Icon: person
- Attendance (الحضور) - Icon: checkmark
- Fees (الرسوم) - Icon: money
- Grades (المراحل) - Icon: book
- Reports (التقارير) - Icon: chart

### 3. Students Management Screens

#### 3.1 Students List Screen
**Header:**
- Title: "قائمة الطلاب" (Students List)
- Search bar with filter icon
- "Add Student" button (إضافة طالب) - Floating action button

**List Items (Card Design):**
- Student photo (circular)
- Student name (الاسم الكامل)
- Grade and classroom info (المرحلة والفصل)
- Attendance status indicator
- Three-dot menu for actions

**Action Menu:**
- View Profile (عرض الملف الشخصي)
- Edit Information (تعديل المعلومات)
- Add Fee Invoice (إضافة فاتورة رسوم)
- Payment Receipt (سند القبض)
- Delete Student (حذف الطالب)

#### 3.2 Add/Edit Student Screen
**Form Sections:**

**Section 1: Personal Information (المعلومات الشخصية)**
- Arabic Name (الاسم بالعربية) - Required
- English Name (الاسم بالإنجليزية) - Required
- Email (البريد الإلكتروني)
- Password (كلمة المرور)
- Gender (الجنس) - Dropdown: ذكر/أنثى
- Nationality (الجنسية) - Dropdown
- Blood Type (فصيلة الدم) - Dropdown
- Date of Birth (تاريخ الميلاد) - Date picker

**Section 2: Academic Information (المعلومات الأكاديمية)**
- Grade (المرحلة الدراسية) - Dropdown
- Classroom (الفصل الدراسي) - Dropdown
- Section (القسم) - Dropdown
- Parent (ولي الأمر) - Dropdown
- Academic Year (السنة الأكاديمية) - Dropdown

**Section 3: Attachments (المرفقات)**
- Upload photo button (رفع الصورة)
- Upload documents button (رفع المستندات)
- Preview uploaded files

**Buttons:**
- Save (حفظ) - Green
- Cancel (إلغاء) - Gray

### 4. Attendance Screens

#### 4.1 Attendance Marking Screen
**Header:**
- Title: "سجل الحضور والغياب" (Attendance Register)
- Date selector (التاريخ)
- Grade/Class filters

**Student List:**
- Student photo and name
- Radio buttons: Present (حاضر) / Absent (غائب)
- Present button - Green color
- Absent button - Red color
- Auto-save functionality

**Bottom Action:**
- Save Attendance (حفظ الحضور) - Green button

#### 4.2 Section-wise Attendance
**Accordion Design:**
- Expandable grade sections
- Each grade shows classrooms
- Each classroom shows sections

**Section Cards:**
- Section name (اسم القسم)
- Classroom name (اسم الفصل)
- Status badge: Active (نشط) / Inactive (غير نشط)
- "View Students" button (عرض الطلاب)

### 5. Teachers Management Screens

#### 5.1 Teachers List Screen
**Header:**
- Title: "قائمة المعلمين" (Teachers List)
- Search and filter options
- "Add Teacher" button (إضافة معلم)

**Teacher Cards:**
- Teacher photo
- Full name (الاسم الكامل)
- Specialization (التخصص)
- Gender (الجنس)
- Joining date (تاريخ الانضمام)
- Edit and delete buttons

#### 5.2 Add/Edit Teacher Screen
**Form Fields:**
- Email (البريد الإلكتروني)
- Password (كلمة المرور)
- Arabic Name (الاسم بالعربية)
- English Name (الاسم بالإنجليزية)
- Specialization (التخصص) - Dropdown
- Gender (الجنس) - Dropdown
- Joining Date (تاريخ الانضمام) - Date picker
- Address (العنوان) - Textarea

### 6. Fees Management Screens

#### 6.1 Fees List Screen
**Header:**
- Title: "إدارة الرسوم" (Fees Management)
- Filter by grade/class/year
- "Add New Fee" button (إضافة رسوم جديدة)

**Fee Cards:**
- Fee name (اسم الرسوم)
- Amount (المبلغ)
- Grade and classroom
- Fee type (نوع الرسوم): Tuition/Bus
- Academic year
- Edit and delete options

#### 6.2 Add/Edit Fee Screen
**Form Layout:**
- Fee Name Arabic (الاسم بالعربية)
- Fee Name English (الاسم بالإنجليزية)
- Amount (المبلغ) - Number input
- Grade Level (المرحلة الدراسية) - Dropdown
- Classroom (الصف الدراسي) - Dropdown
- Academic Year (السنة الدراسية) - Dropdown
- Fee Type (نوع الرسوم) - Dropdown
- Notes (ملاحظات) - Textarea

### 7. Financial Operations Screens

#### 7.1 Fee Invoice Screen
**Header:**
- Student name and info
- Invoice number (رقم الفاتورة)
- Date (التاريخ)

**Invoice Items:**
- Fee type description
- Amount per item
- Total amount (المبلغ الإجمالي)

**Actions:**
- Generate Invoice (إنشاء الفاتورة)
- Print Invoice (طباعة الفاتورة)
- Send to Parent (إرسال لولي الأمر)

#### 7.2 Payment Receipt Screen
**Form Fields:**
- Student selection (اختيار الطالب)
- Amount received (المبلغ المستلم)
- Payment date (تاريخ الدفع)
- Payment method (طريقة الدفع): Cash/Bank Transfer
- Notes (ملاحظات)

**Receipt Preview:**
- Receipt number (رقم السند)
- Student details
- Amount in words and numbers
- Signature area

### 8. Profile and Settings Screens

#### 8.1 User Profile Screen
**Profile Photo:**
- Circular image with edit option
- Change photo button

**Personal Information:**
- Full name (الاسم الكامل)
- Email (البريد الإلكتروني)
- Phone (رقم الهاتف)
- Role (الدور الوظيفي)

**Account Settings:**
- Change password (تغيير كلمة المرور)
- Language preferences (تفضيلات اللغة)
- Notification settings (إعدادات الإشعارات)

#### 8.2 Settings Screen
**System Settings:**
- School name (اسم المدرسة)
- Academic year (السنة الأكاديمية)
- Grading system (نظام التقدير)
- Attendance settings (إعدادات الحضور)

**App Settings:**
- Dark mode toggle (الوضع الليلي)
- Font size (حجم الخط)
- Data synchronization (مزامنة البيانات)
- About app (عن التطبيق)

---

## Mobile-Specific Features

### Navigation
- **Bottom Navigation Bar**: 5 main sections
- **Hamburger Menu**: Additional options
- **Breadcrumb Navigation**: For deep screens
- **Back Button**: Consistent placement

### Gestures
- **Swipe Right**: Open navigation drawer
- **Swipe Left**: Go back
- **Pull to Refresh**: Update data
- **Long Press**: Show context menu

### Notifications
- **Push Notifications**: For important updates
- **In-App Notifications**: For messages and alerts
- **Badge Count**: On app icon

### Offline Support
- **Local Storage**: Cache important data
- **Sync Status**: Show last sync time
- **Offline Mode**: Limited functionality

### Accessibility
- **Screen Reader Support**: Arabic text reading
- **High Contrast Mode**: For better visibility
- **Large Text Option**: For accessibility
- **Voice Commands**: Where possible

---

## Technical Specifications

### Data Models
```
Student: {
  id, name_ar, name_en, email, gender_id, 
  grade_id, classroom_id, section_id, parent_id,
  date_birth, academic_year, photos[]
}

Teacher: {
  id, name_ar, name_en, email, password,
  specialization_id, gender_id, joining_date, address
}

Attendance: {
  id, student_id, grade_id, classroom_id, section_id,
  attendance_date, attendance_status
}

Fee: {
  id, title_ar, title_en, amount, grade_id,
  classroom_id, year, fee_type, description
}
```

### API Integration
- **Base URL**: Configurable
- **Authentication**: Bearer token
- **Endpoints**: RESTful design
- **Error Handling**: User-friendly Arabic messages

### Security
- **Data Encryption**: Local storage encryption
- **Session Management**: Auto-logout
- **Input Validation**: Client and server-side
- **Permission System**: Role-based access

---

## User Experience Guidelines

### Onboarding
- **Welcome Screen**: App introduction
- **Login Tutorial**: First-time users
- **Feature Tour**: Highlight key features
- **Quick Start Guide**: Basic operations

### Performance
- **Fast Loading**: Under 3 seconds
- **Smooth Animations**: 60fps transitions
- **Lazy Loading**: Large datasets
- **Image Optimization**: Compressed photos

### Error Handling
- **Network Errors**: Retry options
- **Validation Errors**: Clear Arabic messages
- **System Errors**: Graceful degradation
- **User Feedback**: Loading indicators

---

## Testing Requirements

### Device Compatibility
- **Screen Sizes**: Phone (5"-7"), Tablet (8"-12")
- **Operating Systems**: iOS 12+, Android 8+
- **Orientation**: Portrait primary, Landscape supported
- **Performance**: Low-end device optimization

### User Testing
- **Arabic Users**: Language validation
- **Teachers**: Workflow testing
- **Administrators**: Full feature testing
- **Parents**: Limited access testing

---

This prompt provides comprehensive specifications for creating a fully functional Arabic-language school management mobile app using Google Stitch, covering all major screens, features, and technical requirements.
