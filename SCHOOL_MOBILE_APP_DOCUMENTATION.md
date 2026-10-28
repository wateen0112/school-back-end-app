# Complete Technical & Functional Documentation: School Mobile App (`school_mobile_app`)

This document provides an exhaustive, function-by-function and screen-by-screen breakdown of the entire Flutter codebase, detailing every screen, widget, service, and data model.

---

## 1. Core Architecture & App Initialization (`lib/app/`)

### `api_client.dart`
- **Purpose**: Low-level REST API gateway.
- **Key Functions**:
  - Manages HTTP requests (GET, POST, PUT, DELETE) via Dio.
  - Automatically injects Bearer tokens from secure storage.
  - Intercepts errors to throw structured API exceptions.

### `app_state.dart`
- **Purpose**: Global application state container (`ChangeNotifier`).
- **Key Functions**:
  - `setLocale(Locale locale)`: Switches language between English (`en`) and Arabic (`ar`) and persists preference.
  - `selectedChildId`: Tracks active child context for parent accounts.

### `app_strings.dart`
- **Purpose**: Comprehensive localization dictionary.
- **Key Functions**:
  - `of(BuildContext context, String key)`: Resolves translation keys into localized strings based on active locale.

### `design_system.dart`
- **Purpose**: UI design tokens and constants.
- **Key Functions**:
  - Defines `SchoolColors` (primary, secondary, success, danger, muted).
  - Supplies custom gradients (`SchoolGradients.action`, `AppTheme.promoGradient`).

### `resource_registry.dart`
- **Purpose**: Declarative CRUD metadata registry.
- **Key Functions**:
  - Defines `ResourceDefinition` and `ResourceField` objects for all core backend modules (Students, Teachers, Parents, Grades, Classrooms, Sections, Subjects, Fees, Invoices, Receipts).

---

## 2. Core Infrastructure & Services (`lib/core/`)

### Auth & Routing (`core/auth/`, `core/router/`)
- **`auth_guard.dart`**: Evaluates session validity and route access permissions before navigation.
- **`app_router.dart`**: Configures `GoRouter` navigation rules, auth redirects, and fallback error handling.
- **`session_controller.dart`**: Oversees user authentication state, current role (`admin`, `teacher`, `student`, `parent`, `driver`), and home routes.

### Network & Repositories (`core/network/`, `core/data/`)
- **`api_service.dart`**: Base wrapper for handling API payloads and error handling.
- **`auth_service.dart` & `auth_storage.dart`**: Encapsulates login, logout, token caching, and user data persistence.
- **`exam_service.dart`**: Fetches online quizzes, questions, and submits answers.
- **`school_repository.dart`**: Bridges backend API endpoints with typed `SchoolRecord` domain models.
- **`promotion_service.dart` & `profile_service.dart`**: Manages student graduation/promotion workflows and user profiles.
- **`notification_service.dart`**: Configures local notifications and push handlers.
- **`foreground_notification_poller.dart`**: Periodically checks for new background notifications.

### Shared Widgets (`core/widgets/`)
- **`animated_container.dart`**: Wraps children in entrance animations (`fadeUp`, `fadeScale`).
- **`dynamic_form.dart`**: Renders form fields dynamically from field definitions with validation.
- **`empty_state.dart`**: Displays structured empty/error states with retry buttons.
- **`loading_shimmer.dart`**: Renders placeholder shimmer loading animations.
- **`refreshable_content.dart`**: Ensures any content or empty state supports pull-to-refresh gestures.
- **`school_data_table.dart`**: Renders structured tabular data views.

---

## 3. Top-Level Screens (`lib/screens/`)

### `login_screen.dart`
- **Features**: Email/password authentication, API base URL configuration, quick role-preview switcher.

### `home_shell.dart`
- **Features**: Bottom navigation container hosting the 5 primary tabs: Dashboard, Students, CMS, Attendance, Settings.

### `dashboard_screen.dart`
- **Features**: Role-specific overview. Admin view shows summary statistic cards and module grid. Student view shows welcome banner, attendance stats, today's classes, and weekly schedule.

### `resource_screen.dart`
- **Features**: Generic searchable list view for any `ResourceDefinition`. Handles loading states, pull-to-refresh, real-time query filtering, and edit/delete popup menus.

### `resource_form_screen.dart`
- **Features**: Generic form screen generated from entity field definitions. Validates inputs, handles password/number/date parsing, and submits create/update requests.

### `attendance_screen.dart`
- **Features**: Displays daily student attendance roster with segment buttons for Present/Absent tracking and bulk saving.

### `cms_screen.dart`
- **Features**: Content Management System hub organizing administrative modules and school records.

---

## 4. Feature Modules (`lib/features/`)

### Complaints (`features/complaints/`)
- **`complaints_content.dart`**: Submit new complaints with priority levels, attach files, view complaints list, and reply to existing tickets.

### Exams (`features/exams/`)
- **`exams_list_screen.dart`**: Browse available quizzes.
- **`exam_start_screen.dart`**: Exam instructions and timer setup.
- **`exam_take_screen.dart`**: Active test-taking screen with question pagination and submission.
- **`exam_result_screen.dart`**: Exam score summary and detailed answers review.

### Installments (`features/installments/`)
- **`my_installments_screen.dart`**: Student/parent overview of installment payment progress and dues.
- **`installment_plans_screen.dart` & `installment_plan_detail_screen.dart`**: Fee plan breakdown and tracking.

### Notifications (`features/notifications/`)
- **`notification_screen.dart`**: Full notification inbox with unread filters and mark-as-read actions.
- **`notification_dropdown.dart`**: Popup overlay for quick notification preview.

### Parents (`features/parents/`)
- **`parent_children_content.dart`**: Parent dashboard listing all linked children and their latest monthly reports.
- **`parent_detail_screen.dart`**: Detailed view of parent father/mother information, contact details, and linked student records.
- **`child_details_screen.dart`**: Comprehensive breakdown of a child's school attendance, grades, and activities.

### Paper Exams (`features/paper_exams/`)
- **`teacher_paper_exams_content.dart`**: Teacher management screen for paper exams.
- **`student_marks_content.dart`**: Student marks report view.

### Schedule (`features/schedule/`)
- **`schedule_screen.dart`**: Weekly class timetable.
- **`academic_calendar_screen.dart`**: School events calendar.

### Teacher (`features/teacher/`)
- **`teacher_students_evaluation_content.dart`**: Grade, classroom, section, and subject cascading filter dropdowns. Loads student rosters with checkboxes and opens `_EvaluateSheet` modal to submit monthly evaluations (bad, good, very good, excellent).

### Transportation (`features/transportation/`)
- **`driver_home_content.dart`, `driver_trip_screen.dart`**: Driver route and trip management.
- **`parent_trip_screen.dart`, `parent_transport_content.dart`**: Parent bus tracking view.
- **`transport_bus_map.dart`**: Map widget displaying real-time vehicle GPS coordinates.
- **`driver_location_foreground_service.dart`**: Foreground service broadcasting driver GPS coordinates.
