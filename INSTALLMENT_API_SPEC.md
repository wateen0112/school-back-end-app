# Installment Plans API & Integration Specification

## Base URL
All routes are prefixed with `/api` (e.g. `GET /api/installment-plans`).

## Authentication
- **Read endpoints**: `auth.any` (any authenticated user: admin, student, teacher, parent).
- **Write endpoints**: `auth:api` (admin only).

---

## API Routes

### Installment Plans (Admin write, all read)

| Method | Endpoint | Access | Description |
|--------|----------|--------|-------------|
| GET | `/installment-plans` | any | List all active installment plans |
| GET | `/installment-plans/{id}` | any | Show single plan with installments |
| POST | `/installment-plans` | admin | Create new plan + installments |
| PUT | `/installment-plans/{id}` | admin | Update plan (only if no payments yet) |
| DELETE | `/installment-plans/{id}` | admin | Soft delete plan (only if no payments) |
| POST | `/installment-plans/{id}/generate` | admin | Re-generate student installments for all students in classroom |

### Student Installments

| Method | Endpoint | Access | Description |
|--------|----------|--------|-------------|
| GET | `/student-installments` | admin | List all student installments (paginated, filter by plan/status) |
| GET | `/student-installments/{id}` | admin | Show single student installment with receipts |
| GET | `/my-installments` | any | Current authenticated student's/parent's installments |

### Receipts Integration

Existing endpoint extended:

| Method | Endpoint | Access | Description |
|--------|----------|--------|-------------|
| POST | `/receipt-students` | admin | Create receipt; accepts optional `student_installment_id` |

---

## Request/Response Shapes

### Common Response Wrapper

```json
{
  "success": true,
  "message": "...",
  "data": { ... }
}
```

### 1. List Installment Plans

**Request:** `GET /api/installment-plans`

**Response 200:**

```json
{
  "success": true,
  "message": "Installment plans retrieved successfully",
  "data": [
    {
      "id": 1,
      "title": { "ar": "رسوم الصف الأول أ", "en": "Grade 1-A Fees" },
      "description": null,
      "grade_id": 1,
      "classroom_id": 2,
      "fee_id": 3,
      "total_amount": 2000.00,
      "academic_year": "2026-2027",
      "start_date": "2026-09-01",
      "end_date": "2027-06-01",
      "is_active": true,
      "installments_count": 4,
      "students_count": 25,
      "created_at": "2026-07-01T08:00:00.000000Z",
      "updated_at": "2026-07-01T08:00:00.000000Z"
    }
  ]
}
```

### 2. Show Single Plan

**Request:** `GET /api/installment-plans/1`

**Response 200:**

```json
{
  "success": true,
  "message": "Installment plan retrieved successfully",
  "data": {
    "id": 1,
    "title": { "ar": "رسوم الصف الأول أ", "en": "Grade 1-A Fees" },
    "description": null,
    "grade_id": 1,
    "classroom_id": 2,
    "fee_id": 3,
    "total_amount": 2000.00,
    "academic_year": "2026-2027",
    "start_date": "2026-09-01",
    "end_date": "2027-06-01",
    "is_active": true,
    "grade": { "id": 1, "name": "First Grade" },
    "classroom": { "id": 2, "name_class": "1-A" },
    "fee": { "id": 3, "title": { "ar": "رسوم دراسية", "en": "Tuition Fees" }, "amount": 2000 },
    "installments": [
      {
        "id": 1,
        "installment_plan_id": 1,
        "title": "الدفعة الأولى",
        "amount": 500.00,
        "due_date": "2026-09-01",
        "sort_order": 1,
        "description": null
      }
    ],
    "created_at": "2026-07-01T08:00:00.000000Z",
    "updated_at": "2026-07-01T08:00:00.000000Z"
  }
}
```

### 3. Create Installment Plan

**Request:** `POST /api/installment-plans`

```json
{
  "title": { "ar": "رسوم الصف الأول أ", "en": "Grade 1-A Fees" },
  "description": { "ar": "رسوم العام الدراسي", "en": "Academic year fees" },
  "grade_id": 1,
  "classroom_id": 2,
  "fee_id": 3,
  "total_amount": 2000.00,
  "academic_year": "2026-2027",
  "start_date": "2026-09-01",
  "end_date": "2027-06-01",
  "installments": [
    { "title": "الدفعة الأولى", "amount": 500.00, "due_date": "2026-09-01", "description": null },
    { "title": "الدفعة الثانية", "amount": 500.00, "due_date": "2026-11-01", "description": null },
    { "title": "الدفعة الثالثة", "amount": 500.00, "due_date": "2027-01-01", "description": null },
    { "title": "الدفعة الرابعة", "amount": 500.00, "due_date": "2027-03-01", "description": null }
  ]
}
```

**Response 201:**

```json
{
  "success": true,
  "message": "Installment plan created successfully",
  "data": {
    "id": 1,
    "title": { "ar": "رسوم الصف الأول أ", "en": "Grade 1-A Fees" },
    "total_amount": 2000.00,
    "installments_count": 4,
    "students_count": 25,
    "created_at": "2026-07-01T08:00:00.000000Z"
  }
}
```

**Error 422 (sum mismatch):**

```json
{
  "success": false,
  "message": "The sum of installments must equal the total amount.",
  "errors": {
    "installments": ["The sum of installments must equal the total amount."]
  }
}
```

### 4. Update Installment Plan

**Request:** `PUT /api/installment-plans/1`

```json
{
  "title": { "ar": "رسوم الصف الأول أ - محدث", "en": "Grade 1-A Fees - Updated" },
  "total_amount": 2200.00,
  "installments": [
    { "id": 1, "title": "الدفعة الأولى", "amount": 600.00, "due_date": "2026-09-01" },
    { "id": 2, "title": "الدفعة الثانية", "amount": 600.00, "due_date": "2026-11-01" },
    { "title": "دفعة إضافية", "amount": 1000.00, "due_date": "2027-01-01" }
  ]
}
```

**Response 200:**

```json
{
  "success": true,
  "message": "Installment plan updated successfully",
  "data": { "id": 1, "title": "...", "total_amount": 2200.00 }
}
```

**Error 422 (already paid):**

```json
{
  "success": false,
  "message": "Cannot update plan because some installments have already been paid.",
  "errors": { "installments": ["Cannot update plan because some installments have already been paid."] }
}
```

### 5. Delete Installment Plan

**Request:** `DELETE /api/installment-plans/1`

**Response 200:**

```json
{
  "success": true,
  "message": "Installment plan deleted successfully"
}
```

### 6. Generate Student Installments

**Request:** `POST /api/installment-plans/1/generate`

**Response 200:**

```json
{
  "success": true,
  "message": "Student installments generated successfully",
  "data": {
    "generated_count": 25,
    "students_count": 25
  }
}
```

### 7. List Student Installments (Admin)

**Request:** `GET /api/student-installments?plan_id=1&status=pending`

**Response 200:**

```json
{
  "success": true,
  "message": "Student installments retrieved successfully",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "installment_plan_id": 1,
        "installment_id": 1,
        "student_id": 10,
        "fee_invoice_id": 55,
        "amount": 500.00,
        "paid_amount": 0.00,
        "remaining_amount": 500.00,
        "status": "pending",
        "due_date": "2026-09-01",
        "paid_at": null,
        "reminder_sent_at": null,
        "overdue_sent_at": null,
        "student": { "id": 10, "name": "Ahmed" },
        "installment": { "id": 1, "title": "الدفعة الأولى" },
        "plan": { "id": 1, "title": "..." }
      }
    ],
    "total": 100,
    "per_page": 20
  }
}
```

### 8. Show Student Installment

**Request:** `GET /api/student-installments/1`

**Response 200:**

```json
{
  "success": true,
  "message": "Student installment retrieved successfully",
  "data": {
    "id": 1,
    "installment_plan_id": 1,
    "installment_id": 1,
    "student_id": 10,
    "fee_invoice_id": 55,
    "amount": 500.00,
    "paid_amount": 250.00,
    "remaining_amount": 250.00,
    "status": "partial",
    "due_date": "2026-09-01",
    "paid_at": "2026-08-28T10:00:00.000000Z",
    "reminder_sent_at": "2026-08-29T08:00:00.000000Z",
    "overdue_sent_at": null,
    "student": { "id": 10, "name": "Ahmed" },
    "installment": { "id": 1, "title": "الدفعة الأولى", "due_date": "2026-09-01" },
    "plan": { "id": 1, "title": "..." },
    "receipts": [
      { "id": 20, "date": "2026-08-28", "debit": 250.00, "description": "..." }
    ]
  }
}
```

### 9. My Installments (Student/Parent)

**Request:** `GET /api/my-installments`

**Query Parameters (optional):**

| Parameter | Type | Description |
|-----------|------|-------------|
| `status` | string | Single status or comma-separated list: `pending`, `partial`, `paid`, `overdue` |
| `urgency` | string | Single urgency or comma-separated list: `urgent`, `upcoming`, `normal` |

**Response 200 for student:**

```json
{
  "success": true,
  "message": "My installments retrieved successfully",
  "data": {
    "summary": {
      "total_installments": 4,
      "paid_count": 1,
      "pending_count": 2,
      "partial_count": 0,
      "overdue_count": 1,
      "urgent_count": 2
    },
    "installments": [
      {
        "id": 1,
        "plan_title": { "ar": "رسوم الصف الأول أ", "en": "Grade 1-A Fees" },
        "installment_title": "الدفعة الأولى",
        "amount": 500.00,
        "paid_amount": 0.00,
        "remaining_amount": 500.00,
        "status": "overdue",
        "due_date": "2026-06-25",
        "is_overdue": true,
        "days_until_due": -6,
        "urgency": "urgent"
      },
      {
        "id": 2,
        "plan_title": { "ar": "رسوم الصف الأول أ", "en": "Grade 1-A Fees" },
        "installment_title": "الدفعة الثانية",
        "amount": 500.00,
        "paid_amount": 0.00,
        "remaining_amount": 500.00,
        "status": "pending",
        "due_date": "2026-07-03",
        "is_overdue": false,
        "days_until_due": 2,
        "urgency": "urgent"
      }
    ]
  }
}
```

**Response 200 for parent:**

```json
{
  "success": true,
  "message": "Children installments retrieved successfully",
  "data": {
    "summary": {
      "total_installments": 8,
      "paid_count": 3,
      "pending_count": 3,
      "partial_count": 1,
      "overdue_count": 1,
      "urgent_count": 2
    },
    "children": [
      {
        "student_id": 10,
        "student_name": "Ahmed",
        "installments": [
          {
            "id": 1,
            "plan_title": { "ar": "رسوم الصف الأول أ", "en": "Grade 1-A Fees" },
            "installment_title": "الدفعة الأولى",
            "amount": 500.00,
            "paid_amount": 0.00,
            "remaining_amount": 500.00,
            "status": "overdue",
            "due_date": "2026-06-25",
            "is_overdue": true,
            "days_until_due": -6,
            "urgency": "urgent"
          }
        ]
      }
    ]
  }
}
```

### 10. Create Receipt (with installment)

**Request:** `POST /api/receipt-students`

```json
{
  "student_id": 10,
  "debit": 250.00,
  "description": "سداد جزئي للدفعة الأولى",
  "student_installment_id": 1
}
```

**Response 201:**

```json
{
  "success": true,
  "message": "Receipt created and installment updated successfully",
  "data": {
    "receipt": { "id": 20, "date": "2026-07-01", "debit": 250.00 },
    "installment": { "id": 1, "paid_amount": 250.00, "remaining_amount": 250.00, "status": "partial" }
  }
}
```

---

## Web Routes

| Method | URI | Controller@method | Name |
|--------|-----|-------------------|------|
| GET | `/installment-plans` | `InstallmentPlanController@index` | `installment-plans.index` |
| GET | `/installment-plans/create` | `InstallmentPlanController@create` | `installment-plans.create` |
| POST | `/installment-plans` | `InstallmentPlanController@store` | `installment-plans.store` |
| GET | `/installment-plans/{id}` | `InstallmentPlanController@show` | `installment-plans.show` |
| GET | `/installment-plans/{id}/edit` | `InstallmentPlanController@edit` | `installment-plans.edit` |
| PUT | `/installment-plans/{id}` | `InstallmentPlanController@update` | `installment-plans.update` |
| DELETE | `/installment-plans/{id}` | `InstallmentPlanController@destroy` | `installment-plans.destroy` |
| POST | `/installment-plans/{id}/generate` | `InstallmentPlanController@generate` | `installment-plans.generate` |
| GET | `/student-installments` | `StudentInstallmentController@index` | `student-installments.index` |
| GET | `/student-installments/{id}` | `StudentInstallmentController@show` | `student-installments.show` |

---

## Mobile Flutter Endpoints to Consume

The mobile app should call:

- `GET /api/my-installments` — for student/parent to see due installments.
- `GET /api/installment-plans` — for admin (if admin mobile UI exists).
- `POST /api/receipt-students` — for admin to record payment (if mobile admin).

### Notification Handling

When FCM payload contains `type: installment_reminder` or `type: installment_overdue`, app should:
1. Show local notification.
2. On tap, navigate to My Installments screen.

### Data Models for Flutter

```dart
class MyInstallment {
  final int id;
  final Map<String, String> planTitle;
  final String installmentTitle;
  final double amount;
  final double paidAmount;
  final double remainingAmount;
  final String status; // pending | partial | paid | overdue
  final String dueDate;
  final bool isOverdue;
  final int? daysUntilDue;
}
```

---

## Backend Business Rules

1. `sum(installments.amount)` must equal `total_amount` (±0.01).
2. `classroom_id` must belong to `grade_id`.
3. Installment `due_date` values should be ascending (optional warning, not strict error).
4. On create, auto-generate `StudentInstallment` records for all students in classroom.
5. On create, generate one `Fee_invoice` + `StudentAccount` per student for total amount.
6. Update allowed only if no `StudentInstallment` has `status IN ('paid', 'partial')`.
7. Delete allowed only if no `StudentInstallment` has `status IN ('paid', 'partial')`.
8. Receipt with `student_installment_id` updates the installment status.
9. Cron command sends reminder 3 days before due date and overdue notice after due date.

---

## Notification Payloads

### Reminder (3 days before)

```json
{
  "title": "تذكير بموعد دفعة رسوم دراسية",
  "body": "عزيزي/عزيزتي {{student_name}}، تذكير باقتراب موعد سداد الدفعة {{installment_title}} بمبلغ {{amount}} بتاريخ {{due_date}}.",
  "data": {
    "type": "installment_reminder",
    "student_installment_id": 1,
    "installment_plan_id": 1
  }
}
```

### Overdue

```json
{
  "title": "تأخر سداد دفعة رسوم دراسية",
  "body": "عزيزي/عزيزتي {{student_name}}، لقد تجاوز موعد سداد الدفعة {{installment_title}} بمبلغ {{amount}} المستحق بتاريخ {{due_date}}. يرجى السداد في أقرب وقت.",
  "data": {
    "type": "installment_overdue",
    "student_installment_id": 1,
    "installment_plan_id": 1
  }
}
```
