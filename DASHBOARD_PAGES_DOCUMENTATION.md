# School Management System - Dashboard Pages Documentation

**Generated:** 2026-10-21 14:16:02
**Total Pages:** 81

---

## Table of Contents

1. [AcademicYearsPage](#academicyearspage)
2. [AnnouncementsPage](#announcementspage)
3. [AttendancePage](#attendancepage)
4. [AuditLogsPage](#auditlogspage)
5. [BranchesPage](#branchespage)
6. [ClassroomsPage](#classroomspage)
7. [ComplaintDetailPage](#complaintdetailpage)
8. [ComplaintsPage](#complaintspage)
9. [DashboardPage](#dashboardpage)
10. [DiscountsPage](#discountspage)
11. [EvaluationDetailPage](#evaluationdetailpage)
12. [EvaluationsPage](#evaluationspage)
13. [ExpenseCategoriesPage](#expensecategoriespage)
14. [ExpensesPage](#expensespage)
15. [FeeInvoicesPage](#feeinvoicespage)
16. [FeesPage](#feespage)
17. [FinancialReportsPage](#financialreportspage)
18. [GradePassFailRulesPage](#gradepassfailrulespage)
19. [GradeTranscriptSettingsPage](#gradetranscriptsettingspage)
20. [GradesPage](#gradespage)
21. [GraduatedPage](#graduatedpage)
22. [HolidaysPage](#holidayspage)
23. [InstallmentPlanDetailPage](#installmentplandetailpage)
24. [InstallmentPlanFormPage](#installmentplanformpage)
25. [InstallmentPlansPage](#installmentplanspage)
26. [IntegrationsPage](#integrationspage)
27. [LibraryPage](#librarypage)
28. [LoginPage](#loginpage)
29. [MyNotificationsPage](#mynotificationspage)
30. [NotificationsPage](#notificationspage)
31. [OnlineClassesPage](#onlineclassespage)
32. [PaperExamMarksPage](#paperexammarkspage)
33. [PaperExamsPage](#paperexamspage)
34. [ParentDashboardPage](#parentdashboardpage)
35. [ParentDetailPage](#parentdetailpage)
36. [ParentsPage](#parentspage)
37. [PaymentsPage](#paymentspage)
38. [PendingRequestsPage](#pendingrequestspage)
39. [PermissionTemplatesPage](#permissiontemplatespage)
40. [PlansPage](#planspage)
41. [PlatformAdminsPage](#platformadminspage)
42. [PlatformDashboardPage](#platformdashboardpage)
43. [PlatformSettingsPage](#platformsettingspage)
44. [ProcessingFeePage](#processingfeepage)
45. [ProfitLossPage](#profitlosspage)
46. [PromotionPage](#promotionpage)
47. [QuestionsPage](#questionspage)
48. [QuizzesPage](#quizzespage)
49. [ReceiptsPage](#receiptspage)
50. [RewardsPenaltiesPage](#rewardspenaltiespage)
51. [RolesPage](#rolespage)
52. [SalariesPage](#salariespage)
53. [SchedulePeriodsPage](#scheduleperiodspage)
54. [SchoolsPage](#schoolspage)
55. [SectionSubjectTeachersPage](#sectionsubjectteacherspage)
56. [SectionsPage](#sectionspage)
57. [SecurityPoliciesPage](#securitypoliciespage)
58. [SettingsPage](#settingspage)
59. [SpecializationsPage](#specializationspage)
60. [StudentDashboardPage](#studentdashboardpage)
61. [StudentFormPage](#studentformpage)
62. [StudentInstallmentDetailPage](#studentinstallmentdetailpage)
63. [StudentInstallmentsPage](#studentinstallmentspage)
64. [StudentReportDetailPage](#studentreportdetailpage)
65. [StudentReportPage](#studentreportpage)
66. [StudentReportsPage](#studentreportspage)
67. [StudentTranscriptsPage](#studenttranscriptspage)
68. [StudentsPage](#studentspage)
69. [SubjectGradeSettingsPage](#subjectgradesettingspage)
70. [SubjectsPage](#subjectspage)
71. [SubscriptionsPage](#subscriptionspage)
72. [TeacherAvailabilityPage](#teacheravailabilitypage)
73. [TeacherDashboardPage](#teacherdashboardpage)
74. [TeacherDetailsPage](#teacherdetailspage)
75. [TeacherFormPage](#teacherformpage)
76. [TeacherSalaryHistoryPage](#teachersalaryhistorypage)
77. [TeacherWorkHoursPage](#teacherworkhourspage)
78. [TeachersPage](#teacherspage)
79. [TimetableScenariosPage](#timetablescenariospage)
80. [TranscriptPreviewPage](#transcriptpreviewpage)
81. [WeeklyProgramsPage](#weeklyprogramspage)

---

## AcademicYearsPage

**File:** `eduadmin-dashboard/src/pages/AcademicYearsPage.tsx`

### Imports
```tsx
import { useEffect, useMemo, useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
import { academicYearsApi, schedulePeriodsApi } from '../api/client';
import { type AcademicYear, type SchedulePeriod, getName } from '../types';
import { EditModal, ConfirmModal, PageLoader } from '../components';
import { useAuth } from '../context/AuthContext';
import { toast } from '../components/Toast';
```

### Components Used
```tsx
<PageLoader />
<EditModal
<ConfirmModal
```

### API Calls
```
queryFn: () => academicYearsApi.calendar(calendarYear!.id),
await schedulePeriodsApi.update(year.id, periodRows.map((r) => ({
mutationFn: (termId: number) => academicYearsApi.setTermCurrent(termId),
? await academicYearsApi.update(editing.id, payload)
mutationFn: (id: number) => academicYearsApi.remove(id),
queryFn: () => schedulePeriodsApi.byYear(editing!.id),
queryFn: () => academicYearsApi.terms(termsFor!),
: await academicYearsApi.create(payload);
queryFn: () => academicYearsApi.list(),
mutationFn: ({ id, terms }: { id: number; terms?: any }) => academicYearsApi.setActive(id, terms),
```

### State / Modals
```tsx
isOpen={!!activeTarget}
isOpen={formOpen}
isOpen={!!deleteTarget}
isOpen={!!calendarYear}
```

### Filters / Search State
```tsx
() => years.filter((y) => y.status !== 'archived' && y.status !== 'draft'),
if (formDay === 'all') return periodRows.filter((r) => r.day_of_week === null);
.filter((b): b is { start: number; end: number; name: string } => b.start !== null && b.end !== null && b.end > b.start)
onClick={() => commitFormDayRows(formDayRows.filter((_, i) => i !== index))}
? f.working_days.filter((d) => d !== day)
...periodRows.filter((r) => r.day_of_week !== null),
onClick={() => setForm({ ...form, breaks: form.breaks.filter((_, i) => i !== idx) })}
...periodRows.filter((r) => r.day_of_week !== formDay),
.filter((b) => b.start && b.end)
const globals = periodRows.filter((r) => r.day_of_week === null);
const dayRows = periodRows.filter((r) => r.day_of_week === formDay);
```

### Permissions
```tsx
const { hasPermission } = useAuth();
const canManage = hasPermission('academic_years.manage');
```

### File Statistics
- Lines: 1100
- Size: 44210 chars

---

## AnnouncementsPage

**File:** `eduadmin-dashboard/src/pages/AnnouncementsPage.tsx`

### Imports
```tsx
import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Plus, Megaphone, Pencil, Trash2, Send } from 'lucide-react';
import { platformApi } from '../api/client';
import { type Announcement, getName } from '../types';
import { DataTable, EditModal, PageLoader, ConfirmModal } from '../components';
import { useApiErrors } from '../lib/useApiErrors';
import { ErrorSummary, FieldError } from '../components/FormErrors';
import { toast } from '../components/Toast';
```

### Components Used
```tsx
<ConfirmModal
<EditModal
if (isLoading) return <PageLoader />;
<DataTable
```

### API Calls
```
mutationFn: (id: number) => platformApi.announcementsBroadcast(id),
className={`input-field w-full rounded-lg border px-3 py-2 text-right ${localErrors.title || formErrors.get('title') ? 'border-red-400' : 'border-white/40'}`} dir="rtl" />
mutationFn: (id: number) => platformApi.announcementsDelete(id),
mutationFn: platformApi.announcementsCreate,
aria-invalid={Boolean(formErrors.get('body_en'))}
aria-invalid={Boolean(localErrors.title || formErrors.get('title'))}
queryFn: platformApi.announcementsList,
aria-invalid={Boolean(localErrors.body || formErrors.get('body'))}
className={`input-field w-full rounded-lg border px-3 py-2 text-right ${formErrors.get('body_en') ? 'border-red-400' : 'border-white/40'}`} dir="ltr" />
aria-invalid={Boolean(formErrors.get('title_en'))}
mutationFn: ({ id, data }: { id: number; data: any }) => platformApi.announcementsUpdate(id, data),
className={`input-field w-full rounded-lg border px-3 py-2 text-right ${localErrors.body || formErrors.get('body') ? 'border-red-400' : 'border-white/40'}`} dir="rtl" />
className={`input-field w-full rounded-lg border px-3 py-2 text-right ${formErrors.get('title_en') ? 'border-red-400' : 'border-white/40'}`} dir="ltr" />
```

### State / Modals
```tsx
isOpen={modalOpen}
isOpen={!!deleteTarget}
const [modalOpen, setModalOpen] = useState(false);
```

### File Statistics
- Lines: 254
- Size: 12665 chars

---

## AttendancePage

**File:** `eduadmin-dashboard/src/pages/AttendancePage.tsx`

### Imports
```tsx
import { useState, useMemo } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Calendar, CheckCircle, XCircle, Clock, AlertCircle, Save, Users, Search, Trash2 } from 'lucide-react';
import { attendanceApi, studentsApi, gradesApi, classroomsApi, sectionsApi } from '../api/client';
import { DataTable, ConfirmModal, PageLoader } from '../components';
import { type Grade, type Classroom, type Section, type Student as ApiStudent, getName } from '../types';
import { toast } from '../components/Toast';
```

### Components Used
```tsx
<PageLoader />
<DataTable
<ConfirmModal
return <PageLoader />;
```

### API Calls
```
mutationFn: (id: string) => attendanceApi.delete(parseInt(id, 10)),
queryFn: () => gradesApi.list(),
attendanceApi.list({
queryFn: () => classroomsApi.list(),
studentsApi.list({
mutationFn: (records: AttendanceInput[]) => attendanceApi.bulkStore(records),
queryFn: () => sectionsApi.list(),
```

### State / Modals
```tsx
isOpen={!!recordToDelete}
```

### Filters / Search State
```tsx
const filteredSections = useMemo(() => {
disabled={!selectedClassroom}
queryKey: ['students-list', { classroom_id: selectedClassroom, section_id: selectedSection }],
grade_id: parseInt(selectedGrade, 10),
if (!selectedGrade) return classrooms;
{filteredClassrooms.map((classroom: Classroom) => (
value={selectedGrade}
const excusedCount = attendanceRecords.filter((r: AttendanceRecord) => r.status === 'excused').length;
section_id: selectedSection ? parseInt(selectedSection, 10) : parseInt(selectedClassroom, 10),
return sections.filter((s: Section) => String(s.Class_id) === selectedClassroom);
{filteredSections.map((section: Section) => (
queryKey: ['attendance-list', { date: selectedDate, classroom_id: selectedClassroom, section_id: selectedSection }],
if (!selectedGrade || !selectedClassroom || !selectedDate) {
const lateCount = attendanceRecords.filter((r: AttendanceRecord) => r.status === 'late').length;
const presentCount = attendanceRecords.filter((r: AttendanceRecord) => r.status === 'present').length;
}, [classrooms, selectedGrade]);
const [selectedClassroom, setSelectedClassroom] = useState('');
classroom_id: selectedClassroom ? parseInt(selectedClassroom, 10) : undefined,
const absentCount = attendanceRecords.filter((r: AttendanceRecord) => r.status === 'absent').length;
disabled={!selectedGrade}
enabled: showStudents && !!selectedClassroom,
const filteredClassrooms = useMemo(() => {
const [selectedGrade, setSelectedGrade] = useState('');
enabled: !!selectedClassroom,
classroom_id: parseInt(selectedClassroom, 10),
return classrooms.filter((c: Classroom) => String(c.Grade_id) === selectedGrade);
}, [sections, selectedClassroom]);
if (!selectedClassroom) {
if (!selectedClassroom) return [];
value={selectedClassroom}
```

### File Statistics
- Lines: 648
- Size: 25700 chars

---

## AuditLogsPage

**File:** `eduadmin-dashboard/src/pages/AuditLogsPage.tsx`

### Imports
```tsx
import { useMemo, useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { ChevronDown, ChevronLeft, FileClock } from 'lucide-react';
import { platformApi, auditApi } from '../api/client';
import { useAuth } from '../context/AuthContext';
import type { AuditLog } from '../types';
import { DataTable, PageLoader } from '../components';
```

### Components Used
```tsx
if (isLoading) return <PageLoader />;
<DataTable
```

### API Calls
```
const fetchLogs = user?.is_super_admin ? platformApi.auditLogs : auditApi.logs;
```

### Filters / Search State
```tsx
() => ({ per_page: 50, page, ...Object.fromEntries(Object.entries(filters).filter(([, v]) => v)) }),
[page, filters]
<input value={filters.entity_type} onChange={setFilter('entity_type')} placeholder="الكيان (مثال: Student)"
{expanded !== null && logs.filter((l) => l.id === expanded).map((l) => <DiffPanel key={l.id} log={l} />)}
<input type="date" value={filters.to} onChange={setFilter('to')} className="rounded-lg border border-gray-200 bg-white px-2 py-1.5 text-sm" /></label>
<input value={filters.action} onChange={setFilter('action')} placeholder="الإجراء (مثال: student.updated)"
const [filters, setFilters] = useState({ action: '', entity_type: '', q: '', from: '', to: '' });
<input value={filters.q} onChange={setFilter('q')} placeholder="بحث في الوصف"
const setFilter = (k: keyof typeof filters) => (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
<input type="date" value={filters.from} onChange={setFilter('from')} className="rounded-lg border border-gray-200 bg-white px-2 py-1.5 text-sm" /></label>
```

### File Statistics
- Lines: 193
- Size: 9240 chars

---

## BranchesPage

**File:** `eduadmin-dashboard/src/pages/BranchesPage.tsx`

### Imports
```tsx
import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Plus, Building, Pencil, Trash2 } from 'lucide-react';
import { platformApi, schoolsApi } from '../api/client';
import { type SchoolBranch, type SchoolRecord, getName } from '../types';
import { DataTable, EditModal, PageLoader, ConfirmModal } from '../components';
import { toast } from '../components/Toast';
```

### Components Used
```tsx
<ConfirmModal
<EditModal
if (isLoading) return <PageLoader />;
<DataTable
```

### API Calls
```
queryFn: () => platformApi.branchesList(),
mutationFn: platformApi.branchesCreate,
mutationFn: (id: number) => platformApi.branchesDelete(id),
const { data: schools } = useQuery({ queryKey: ['schools'], queryFn: schoolsApi.list, placeholderData: (prev) => prev as any });
mutationFn: ({ id, data }: { id: number; data: any }) => platformApi.branchesUpdate(id, data),
```

### State / Modals
```tsx
isOpen={modalOpen}
isOpen={!!deleteTarget}
const [modalOpen, setModalOpen] = useState(false);
```

### File Statistics
- Lines: 190
- Size: 10153 chars

---

## ClassroomsPage

**File:** `eduadmin-dashboard/src/pages/ClassroomsPage.tsx`

### Imports
```tsx
import { useState, useMemo } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Plus, Pencil, Trash2, School, Search } from 'lucide-react';
import { classroomsApi, gradesApi } from '../api/client';
import { type Classroom, type Grade, getName } from '../types';
import { DataTable, ConfirmModal, PageLoader, EditModal } from '../components';
import { getChangedFields } from '../lib/diff';
import { toast } from '../components/Toast';
```

### Components Used
```tsx
<ConfirmModal
<EditModal
if (isLoading) return <PageLoader />;
<DataTable
```

### API Calls
```
return classroomsApi.list();
mutationFn: classroomsApi.deleteAll,
mutationFn: classroomsApi.delete,
return classroomsApi.filterByGrade(Number(filterGradeId));
mutationFn: ({ id, data }: { id: number; data: Partial<Classroom> }) => classroomsApi.update(id, data),
queryFn: gradesApi.list,
mutationFn: classroomsApi.create,
```

### State / Modals
```tsx
isOpen={showBulkDelete}
isOpen={editModalOpen}
isOpen={deleteId !== null}
```

### Filters / Search State
```tsx
prev.includes(id) ? prev.filter((sid) => sid !== id) : [...prev, id]
}, [classrooms, searchTerm, grades]);
value={searchTerm}
data={(filteredClassroomsBySearch as any) || []}
queryKey: ['classrooms', filterGradeId],
value={filterGradeId}
<span className="text-sm text-gray-500 whitespace-nowrap">{filteredClassroomsBySearch?.length ?? 0} فصل</span>
return classroomsApi.filterByGrade(Number(filterGradeId));
const term = searchTerm.trim().toLowerCase();
if (filterGradeId) {
const [filterGradeId, setFilterGradeId] = useState<string>('');
return (classrooms as Classroom[] | undefined)?.filter((c) => {
{filterGradeId && (
emptyMessage={searchTerm || filterGradeId ? 'لا توجد نتائج مطابقة للبحث' : 'لا توجد فصول دراسية'}
const filteredClassroomsBySearch = useMemo(() => {
onChange={(e) => setSearchTerm(e.target.value)}
const [searchTerm, setSearchTerm] = useState('');
```

### File Statistics
- Lines: 472
- Size: 17959 chars

---

## ComplaintDetailPage

**File:** `eduadmin-dashboard/src/pages/ComplaintDetailPage.tsx`

### Imports
```tsx
import { useState, useRef } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { ArrowRight, Send, Paperclip, X, CheckCircle, Clock, AlertCircle, XCircle, FileText } from 'lucide-react';
import { complaintsApi } from '../api/client';
import { PageLoader } from '../components';
import { toast } from '../components/Toast';
```

### Components Used
```tsx
if (isLoading) return <PageLoader />;
```

### API Calls
```
complaintsApi.reply(complaintId, formData),
complaintsApi.update(complaintId, { status }),
queryFn: () => complaintsApi.get(Number(id)),
```

### Filters / Search State
```tsx
setReplyFiles((prev) => prev.filter((_, i) => i !== index));
```

### File Statistics
- Lines: 274
- Size: 11849 chars

---

## ComplaintsPage

**File:** `eduadmin-dashboard/src/pages/ComplaintsPage.tsx`

### Imports
```tsx
import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useNavigate } from 'react-router-dom';
import { MessageSquare, Trash2, Eye } from 'lucide-react';
import { complaintsApi } from '../api/client';
import { ConfirmModal, PageLoader } from '../components';
import { toast } from '../components/Toast';
```

### Components Used
```tsx
if (isLoading) return <PageLoader />;
<ConfirmModal
```

### API Calls
```
mutationFn: (id: number) => complaintsApi.delete(id),
queryFn: () => complaintsApi.list(filter ? { status: filter } : undefined),
```

### Filters / Search State
```tsx
queryKey: ['complaints', filter],
const [filter, setFilter] = useState<string>('');
queryFn: () => complaintsApi.list(filter ? { status: filter } : undefined),
```

### File Statistics
- Lines: 156
- Size: 6804 chars

---

## DashboardPage

**File:** `eduadmin-dashboard/src/pages/DashboardPage.tsx`

### Imports
```tsx
import { useQuery } from '@tanstack/react-query';
import { useNavigate } from 'react-router-dom';
import {
import { attendanceApi, dashboardApi, programsApi } from '../api/client';
import type { Attendance } from '../types';
import { getName } from '../types';
import type { WeeklyProgram, TodayScheduleResponse } from '../types';
import { cn } from '../lib/utils';
import { SkeletonCard } from '../components/LoadingSpinner';
import { PendingRequestsFab } from '../components/PendingRequestsFab';
import {
import { toast } from '../components/Toast';
```

### API Calls
```
const gradeGroup = grades.get(gradeRaw)!;
stages.get(stageRaw)!.grades.push(gg);
queryFn: async () => (await dashboardApi.index()) ?? {},
const secs = sectionsInGrade.get(secKey)!;
const sec = secs.get(secLabel)!;
queryFn: () => programsApi.today().then((r: any) => r as TodayScheduleResponse),
const entry = map.get(sid)!;
queryFn: () => attendanceApi.list({ date: todayStr }) as Promise<Attendance[]>,
queryFn: () => programsApi.list(),
const grades = gradesInStage.get(stageRaw)!;
color: levelColors.get(level)!,
```

### Filters / Search State
```tsx
const absentCount = todayAttendance.filter(isAbsentRecord).length;
const presentCount = todayAttendance.filter((r) => !isAbsentRecord(r)).length;
const activeSectionsList = (todaySchedule?.sections || []).filter((s) => s.current);
value={searchTerm}
{filteredStudents.map((student, idx) => {
const liveSections = (todayData?.sections || []).filter((s) => s.current);
{filteredStudents.length === 0 ? (
.filter((item) => Number(item.count) > 0)
{searchTerm ? 'لا توجد نتائج مطابقة' : 'لا يوجد طلاب مسجلين حالياً'}
const currentIds = new Set(programs.filter(isCurrent).map((p) => p.id));
const q = searchTerm.trim().toLowerCase();
const dayLessons = programs.filter((p) => p.day_of_week === dayFilter);
onChange={(e) => setSearchTerm(e.target.value)}
const [searchTerm, setSearchTerm] = useState('');
const filteredStudents = lastFiveStudents.filter((student) => {
```

### File Statistics
- Lines: 1416
- Size: 58904 chars

---

## DiscountsPage

**File:** `eduadmin-dashboard/src/pages/DiscountsPage.tsx`

### Imports
```tsx
import { useState, useRef, useEffect } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Plus, Pencil, Trash2, BadgePercent, ChevronDown, Check } from 'lucide-react';
import { studentDiscountsApi, studentsApi, academicYearsApi } from '../api/client';
import { DataTable, ConfirmModal, PageLoader } from '../components';
import { getName } from '../types';
import type { LocalizedName } from '../types';
import { toast } from '../components/Toast';
```

### Components Used
```tsx
<DataTable
<ConfirmModal
return <PageLoader />;
```

### API Calls
```
studentDiscountsApi.update(id, {
studentDiscountsApi.update(id, { active }),
studentDiscountsApi.create({
mutationFn: (id: number) => studentDiscountsApi.delete(id),
queryFn: () => studentsApi.list(),
queryFn: () => academicYearsApi.list(),
queryFn: () => studentDiscountsApi.list(),
```

### Filters / Search State
```tsx
? selected.filter((s) => s !== label)
```

### File Statistics
- Lines: 614
- Size: 21693 chars

---

## EvaluationDetailPage

**File:** `eduadmin-dashboard/src/pages/EvaluationDetailPage.tsx`

### Imports
```tsx
import { useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import {
import { evaluationsApi, studentsApi, teachersApi, subjectsApi, gradesApi, classroomsApi } from '../api/client';
import { getName } from '../types';
import { PageLoader } from '../components';
import { PieChart, Pie, Cell, ResponsiveContainer, Tooltip, BarChart, Bar, XAxis, YAxis, CartesianGrid } from 'recharts';
```

### Components Used
```tsx
<PageLoader />
```

### API Calls
```
const { data: classrooms } = useQuery({ queryKey: ['classrooms'], queryFn: () => classroomsApi.list() });
const { data: grades } = useQuery({ queryKey: ['grades'], queryFn: () => gradesApi.list() });
return evaluationsApi.reportBySubject(evaluation.subject_id, reportParams);
return evaluationsApi.reportByGrade(evaluation.grade_id, reportParams);
const { data: subjects } = useQuery({ queryKey: ['subjects'], queryFn: () => subjectsApi.list() });
return evaluationsApi.reportByClassroom(evaluation.classroom_id, reportParams);
return evaluationsApi.reportByTeacher(evaluation.teacher_id, reportParams);
const { data: students } = useQuery({ queryKey: ['students'], queryFn: () => studentsApi.list() });
const { data: teachers } = useQuery({ queryKey: ['teachers'], queryFn: () => teachersApi.list() });
return evaluationsApi.reportByStudent(evaluation.student_id, reportParams);
queryFn: () => evaluationsApi.get(evaluationId),
```

### Filters / Search State
```tsx
].filter(d => d.value > 0) : [];
```

### File Statistics
- Lines: 535
- Size: 25053 chars

---

## EvaluationsPage

**File:** `eduadmin-dashboard/src/pages/EvaluationsPage.tsx`

### Imports
```tsx
import { useState, useEffect } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
import { evaluationsApi, gradesApi, classroomsApi, teachersApi, subjectsApi, studentsApi } from '../api/client';
import { useClassroomsByGrade, useSectionsByClassroom } from '../hooks/useDependentOptions';
import { getName } from '../types';
import { EditModal, ConfirmModal } from '../components';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { toast } from '../components/Toast';
```

### Components Used
```tsx
<EditModal
<ConfirmModal
```

### API Calls
```
queryFn: () => evaluationsApi.list(Object.keys(filters).length > 0 ? filters : undefined),
const { data: classrooms } = useQuery({ queryKey: ['classrooms'], queryFn: () => classroomsApi.list() });
mutationFn: (data: EvaluationFormData) => evaluationsApi.create(data),
const { data: grades } = useQuery({ queryKey: ['grades'], queryFn: () => gradesApi.list() });
evaluationsApi.update(id, data),
const { data: subjects } = useQuery({ queryKey: ['subjects'], queryFn: () => subjectsApi.list() });
const { data: teachers } = useQuery({ queryKey: ['teachers'], queryFn: () => teachersApi.list() });
const { data: students } = useQuery({ queryKey: ['students'], queryFn: () => studentsApi.list() });
const studentId = searchParams.get('student_id');
mutationFn: (id: number) => evaluationsApi.delete(id),
```

### State / Modals
```tsx
isOpen={editModalOpen}
isOpen={showForm}
isOpen={deleteId !== null}
```

### Filters / Search State
```tsx
const veryGood = list.filter((e: Evaluation) => e.evaluation_level === 'very_good').length;
onChange={(e) => setFilters({ ...filters, year: e.target.value ? Number(e.target.value) : undefined })}
value={filters.evaluation_level || ''}
const [filters, setFilters] = useState<Record<string, any>>(() => {
onChange={(e) => setFilters({ ...filters, evaluation_level: e.target.value || undefined })}
onChange={(e) => setFilters({ ...filters, student_id: e.target.value ? Number(e.target.value) : undefined })}
if (filters.evaluation_level) activeFilters.evaluation_level = filters.evaluation_level;
queryFn: () => evaluationsApi.list(Object.keys(filters).length > 0 ? filters : undefined),
queryKey: ['evaluations', filters],
const bad = list.filter((e: Evaluation) => e.evaluation_level === 'bad').length;
if (filters.teacher_id) activeFilters.teacher_id = filters.teacher_id;
value={filters.student_id || ''}
onChange={(e) => setFilters({ ...filters, month: e.target.value ? Number(e.target.value) : undefined })}
onChange={(e) => setFilters({ ...filters, classroom_id: e.target.value ? Number(e.target.value) : undefined })}
if (filters.month) activeFilters.month = filters.month;
if (filters.student_id) activeFilters.student_id = filters.student_id;
onChange={(e) => setFilters({ ...filters, teacher_id: e.target.value ? Number(e.target.value) : undefined })}
onChange={(e) => setFilters({ ...filters, subject_id: e.target.value ? Number(e.target.value) : undefined })}
if (filters.year) activeFilters.year = filters.year;
value={filters.teacher_id || ''}
onChange={(e) => setFilters({ ...filters, grade_id: e.target.value ? Number(e.target.value) : undefined })}
if (filters.grade_id) activeFilters.grade_id = filters.grade_id;
if (filters.subject_id) activeFilters.subject_id = filters.subject_id;
const good = list.filter((e: Evaluation) => e.evaluation_level === 'good').length;
value={filters.classroom_id || ''}
value={filters.grade_id || ''}
value={filters.year || ''}
const excellent = list.filter((e: Evaluation) => e.evaluation_level === 'excellent').length;
if (filters.classroom_id) activeFilters.classroom_id = filters.classroom_id;
value={filters.subject_id || ''}
value={filters.month || ''}
```

### File Statistics
- Lines: 775
- Size: 37140 chars

---

## ExpenseCategoriesPage

**File:** `eduadmin-dashboard/src/pages/ExpenseCategoriesPage.tsx`

### Imports
```tsx
import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Plus, Pencil, Trash2, FolderOpen } from 'lucide-react';
import { expenseCategoriesApi } from '../api/client';
import { DataTable, ConfirmModal, PageLoader } from '../components';
import { getName, type ExpenseCategory } from '../types';
import { useAuth } from '../context/AuthContext';
import { toast } from '../components/Toast';
```

### Components Used
```tsx
<DataTable
<ConfirmModal
return <PageLoader />;
```

### API Calls
```
expenseCategoriesApi.update(id, { name: { ar: data.name_ar, en: data.name_en || data.name_ar }, icon: data.icon || undefined }),
expenseCategoriesApi.create({ name: { ar: data.name_ar, en: data.name_en || data.name_ar }, icon: data.icon || undefined }),
mutationFn: (id: number) => expenseCategoriesApi.delete(id),
queryFn: () => expenseCategoriesApi.list(),
```

### Permissions
```tsx
const { hasPermission } = useAuth();
const canManage = hasPermission('expenses.manage');
```

### File Statistics
- Lines: 281
- Size: 10661 chars

---

## ExpensesPage

**File:** `eduadmin-dashboard/src/pages/ExpensesPage.tsx`

### Imports
```tsx
import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Plus, Pencil, Trash2, Wallet } from 'lucide-react';
import { expensesApi, expenseCategoriesApi } from '../api/client';
import { DataTable, ConfirmModal, PageLoader } from '../components';
import { getName, type Expense, type ExpenseCategory } from '../types';
import { useAuth } from '../context/AuthContext';
import { toast } from '../components/Toast';
```

### Components Used
```tsx
<DataTable
<ConfirmModal
return <PageLoader />;
```

### API Calls
```
expensesApi.update(id, {
mutationFn: (id: number) => expensesApi.delete(id),
queryFn: () => expenseCategoriesApi.list(),
expensesApi.create({
expensesApi.list({
```

### Filters / Search State
```tsx
value={filterCategory}
const [filterFrom, setFilterFrom] = useState('');
const [filterCategory, setFilterCategory] = useState('');
value={filterTo}
queryKey: ['expenses', { category_id: filterCategory, from: filterFrom, to: filterTo }],
category_id: filterCategory ? Number(filterCategory) : undefined,
to: filterTo || undefined,
value={filterFrom}
from: filterFrom || undefined,
const [filterTo, setFilterTo] = useState('');
```

### Permissions
```tsx
const { hasPermission } = useAuth();
const canManage = hasPermission('expenses.manage');
```

### File Statistics
- Lines: 443
- Size: 17212 chars

---

## FeeInvoicesPage

**File:** `eduadmin-dashboard/src/pages/FeeInvoicesPage.tsx`

### Imports
```tsx
import { useState, useEffect } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Plus, Pencil, Trash2, FileText } from 'lucide-react';
import { feeInvoicesApi, studentsApi, gradesApi, classroomsApi, feesApi } from '../api/client';
import { useClassroomsByGrade } from '../hooks/useDependentOptions';
import { DataTable, ConfirmModal, PageLoader } from '../components';
import { getName } from '../types';
import { toast } from '../components/Toast';
```

### Components Used
```tsx
<DataTable
<ConfirmModal
return <PageLoader />;
```

### API Calls
```
queryFn: () => feesApi.list(),
queryFn: () => gradesApi.list(),
queryFn: () => feeInvoicesApi.list(),
mutationFn: (id: number) => feeInvoicesApi.delete(id),
queryFn: () => classroomsApi.list(),
queryFn: () => studentsApi.list(),
feeInvoicesApi.create({
feeInvoicesApi.update(id, {
```

### File Statistics
- Lines: 511
- Size: 18961 chars

---

## FeesPage

**File:** `eduadmin-dashboard/src/pages/FeesPage.tsx`

### Imports
```tsx
import { useState, useEffect } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Plus, Pencil, Trash2, ReceiptText } from 'lucide-react';
import { feesApi, gradesApi, classroomsApi } from '../api/client';
import { useClassroomsByGrade } from '../hooks/useDependentOptions';
import { DataTable, ConfirmModal, PageLoader, EditModal } from '../components';
import { getName } from '../types';
import { getChangedFields } from '../lib/diff';
import { toast } from '../components/Toast';
```

### Components Used
```tsx
<DataTable
<EditModal
<ConfirmModal
return <PageLoader />;
```

### API Calls
```
queryFn: () => feesApi.list(),
mutationFn: (id: number) => feesApi.delete(id),
queryFn: () => gradesApi.list(),
feesApi.update(id, {
queryFn: () => classroomsApi.list(),
feesApi.create({
```

### State / Modals
```tsx
isOpen={editModalOpen}
```

### File Statistics
- Lines: 602
- Size: 22648 chars

---

## FinancialReportsPage

**File:** `eduadmin-dashboard/src/pages/FinancialReportsPage.tsx`

### Imports
```tsx
import { useState, useMemo } from 'react';
import { useQuery } from '@tanstack/react-query';
import {
import {
import { financialReportsApi, gradesApi, studentsApi } from '../api/client';
import { LoadingSpinner } from '../components/LoadingSpinner';
import { cn } from '../lib/utils';
```

### API Calls
```
financialReportsApi.statistics({
queryFn: () => financialReportsApi.overview({ year: filters.year }),
queryFn: () => gradesApi.list(),
financialReportsApi.studentDebts({
financialReportsApi.fundMovement({
studentsApi.list({
queryFn: () => studentsApi.getClassroomsByGrade(Number(filters.grade_id)),
queryFn: () => financialReportsApi.monthlyProfit(apiParams),
```

### Filters / Search State
```tsx
enabled: !!filters.grade_id && showFilters,
classroom_id: filters.classroom_id ? Number(filters.classroom_id) : undefined,
queryKey: ['financial-debts', filters.year, filters.grade_id],
month: filters.month || undefined,
{filters.student_id !== '' && (
if (filters.student_id !== '') params.student_id = Number(filters.student_id);
year: filters.year,
queryKey: ['classrooms-by-grade', filters.grade_id],
queryKey: ['financial-overview', filters.year],
{filters.month !== 0 && (
if (filters.grade_id !== '') count++;
queryKey: ['students-list', filters.grade_id, filters.classroom_id],
{filters.classroom_id !== '' && (
if (filters.classroom_id !== '') params.classroom_id = Number(filters.classroom_id);
? `أرباح ${MONTHS.find((m) => m.value === filters.month)?.label} - ${filters.year}`
grade_id: filters.grade_id ? Number(filters.grade_id) : undefined,
{gradeOptions.find((g) => g.value === filters.grade_id)?.label}
queryKey: ['financial-statistics', filters.year, filters.grade_id, filters.classroom_id],
queryFn: () => studentsApi.getClassroomsByGrade(Number(filters.grade_id)),
queryFn: () => financialReportsApi.overview({ year: filters.year }),
queryKey: ['financial-fund', filters.year, filters.month],
if (filters.month !== 0) count++;
if (filters.grade_id !== '') params.grade_id = Number(filters.grade_id);
{filters.fee_type !== '' && (
const [filters, setFilters] = useState<FilterState>({
if (filters.fee_type !== '') params.fee_type = filters.fee_type;
value={filters.student_id}
}, [filters]);
value={filters.year}
: `الأرباح والخسائر الشهرية - ${filters.year}`}
value={filters.fee_type}
{studentOptions.find((s) => s.value === filters.student_id)?.label}
const params: any = { year: filters.year };
value={filters.grade_id}
if (filters.fee_type !== '') count++;
value={filters.classroom_id}
Grade_id: filters.grade_id ? Number(filters.grade_id) : undefined,
if (filters.month !== 0) params.month = filters.month;
{filters.month !== 0
{classroomOptions.find((c) => c.value === filters.classroom_id)?.label}
```

### File Statistics
- Lines: 1344
- Size: 51226 chars

---

## GradePassFailRulesPage

**File:** `eduadmin-dashboard/src/pages/GradePassFailRulesPage.tsx`

### Imports
```tsx
import { useState, useMemo } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Scale, Save, Trash2, Pencil, X } from 'lucide-react';
import { gradePassFailRulesApi, subjectsApi, gradesApi, classroomsApi } from '../api/client';
import { getName } from '../types';
import { ConfirmModal, PageLoader } from '../components';
import { toast } from '../components/Toast';
```

### Components Used
```tsx
if (isLoading && ready) return <PageLoader />;
<ConfirmModal
```

### API Calls
```
const { data: classrooms } = useQuery({ queryKey: ['classrooms'], queryFn: () => classroomsApi.list() });
const { data: grades } = useQuery({ queryKey: ['grades'], queryFn: () => gradesApi.list() });
mutationFn: (data: any) => gradePassFailRulesApi.save(data),
mutationFn: (id: number) => gradePassFailRulesApi.delete(id),
const { data: subjects } = useQuery({ queryKey: ['subjects'], queryFn: () => subjectsApi.list() });
mutationFn: ({ id, data }: { id: number; data: any }) => gradePassFailRulesApi.update(id, data),
queryFn: () => gradePassFailRulesApi.list(classroomId ? { classroom_id: classroomId } : undefined),
```

### State / Modals
```tsx
isOpen={deleteId !== null}
```

### Filters / Search State
```tsx
setExcludedSubjectIds((prev) => prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id]);
return classrooms.filter((c: any) => c.Grade_id === gradeId || c.grade_id === gradeId);
return subjects.filter((s: any) => s.grade_id === gradeId || s.Grade_id === gradeId);
```

### File Statistics
- Lines: 321
- Size: 16434 chars

---

## GradeTranscriptSettingsPage

**File:** `eduadmin-dashboard/src/pages/GradeTranscriptSettingsPage.tsx`

### Imports
```tsx
import { useState, useMemo } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { FileText, Save } from 'lucide-react';
import { gradeTranscriptSettingsApi, gradesApi, classroomsApi } from '../api/client';
import { getName } from '../types';
import { PageLoader } from '../components';
import { toast } from '../components/Toast';
```

### Components Used
```tsx
if (isLoading && ready) return <PageLoader />;
```

### API Calls
```
queryFn: () => gradeTranscriptSettingsApi.list(classroomId ? { classroom_id: classroomId } : undefined),
const { data: classrooms } = useQuery({ queryKey: ['classrooms'], queryFn: () => classroomsApi.list() });
const { data: grades } = useQuery({ queryKey: ['grades'], queryFn: () => gradesApi.list() });
mutationFn: (data: any) => gradeTranscriptSettingsApi.save(data),
```

### Filters / Search State
```tsx
exam_types: e.target.checked ? [...p.exam_types, opt.value] : p.exam_types.filter((t) => t !== opt.value),
return classrooms.filter((c: any) => c.Grade_id === gradeId || c.grade_id === gradeId);
```

### File Statistics
- Lines: 265
- Size: 12425 chars

---

## GradesPage

**File:** `eduadmin-dashboard/src/pages/GradesPage.tsx`

### Imports
```tsx
import { useState, useMemo } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Plus, Pencil, Trash2, GraduationCap, Search } from 'lucide-react';
import { gradesApi } from '../api/client';
import { type Grade, getName } from '../types';
import { DataTable, ConfirmModal, PageLoader, EditModal } from '../components';
import { getChangedFields } from '../lib/diff';
import { toast } from '../components/Toast';
```

### Components Used
```tsx
<ConfirmModal
<EditModal
if (isLoading) return <PageLoader />;
<DataTable
```

### API Calls
```
mutationFn: ({ id, data }: { id: number; data: Partial<Grade> }) => gradesApi.update(id, data),
queryFn: gradesApi.list,
mutationFn: gradesApi.delete,
mutationFn: gradesApi.create,
```

### State / Modals
```tsx
isOpen={editModalOpen}
isOpen={deleteId !== null}
```

### Filters / Search State
```tsx
value={searchTerm}
emptyMessage={searchTerm ? 'لا توجد نتائج مطابقة للبحث' : 'لا توجد مراحل دراسية'}
}, [grades, searchTerm]);
const term = searchTerm.trim().toLowerCase();
onChange={(e) => setSearchTerm(e.target.value)}
return (grades as Grade[] | undefined)?.filter((g) => {
data={(filteredGrades as any) || []}
const filteredGrades = useMemo(() => {
<span className="text-sm text-gray-500">{filteredGrades?.length ?? 0} مرحلة</span>
const [searchTerm, setSearchTerm] = useState('');
```

### File Statistics
- Lines: 352
- Size: 13114 chars

---

## GraduatedPage

**File:** `eduadmin-dashboard/src/pages/GraduatedPage.tsx`

### Imports
```tsx
import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
import { graduatedApi, studentsApi, gradesApi } from '../api/client';
import { DataTable, ConfirmModal, PageLoader, EditModal } from '../components';
import { useClassroomsByGrade, useSectionsByClassroom } from '../hooks/useDependentOptions';
import { getName, type Grade } from '../types';
import { toast } from '../components/Toast';
```

### Components Used
```tsx
<ConfirmModal
<EditModal
if (isLoading) return <PageLoader />;
<DataTable columns={columns} data={graduatedList} />
```

### API Calls
```
graduatedApi.update(id, data),
mutationFn: graduatedApi.delete,
mutationFn: graduatedApi.create,
queryFn: graduatedApi.list,
studentsApi.list({
queryFn: gradesApi.list,
```

### State / Modals
```tsx
isOpen={showForm}
isOpen={deleteId !== null}
```

### Filters / Search State
```tsx
{filteredStudents?.map((s: StudentOption) => (
queryKey: ['students', 'filtered', gradeId, classroomId, sectionId],
const { data: filteredStudents = [], isLoading: studentsLoading } = useQuery({
```

### File Statistics
- Lines: 419
- Size: 15172 chars

---

## HolidaysPage

**File:** `eduadmin-dashboard/src/pages/HolidaysPage.tsx`

### Imports
```tsx
import { useMemo, useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { CalendarOff, CalendarPlus, Pencil, Plus, Trash2 } from 'lucide-react';
import { academicYearsApi, holidaysApi } from '../api/client';
import { type Holiday, getName } from '../types';
import { EditModal, ConfirmModal, PageLoader } from '../components';
import { useAuth } from '../context/AuthContext';
import { toast } from '../components/Toast';
```

### Components Used
```tsx
<PageLoader />
<EditModal
<ConfirmModal
```

### API Calls
```
editing ? holidaysApi.update(editing.id, payload) : holidaysApi.create(payload),
mutationFn: (id: number) => holidaysApi.remove(id),
return (id: string | null) => (id ? map.get(id) || '' : '');
holidaysApi.list({
queryFn: () => academicYearsApi.list(),
```

### State / Modals
```tsx
isOpen={formOpen}
isOpen={!!deleteTarget}
```

### Filters / Search State
```tsx
// Client-side filtering so the "إجازات" tab excludes events (type = special)
kindFilter === 'holidays' ? holidays.filter((h) => h.type !== 'special') : holidays;
const filteredHolidays =
) : filteredHolidays.length === 0 ? (
{filteredHolidays.map((h) => {
```

### Permissions
```tsx
const canManage = hasPermission('holidays.manage');
const { hasPermission } = useAuth();
```

### File Statistics
- Lines: 378
- Size: 14805 chars

---

## InstallmentPlanDetailPage

**File:** `eduadmin-dashboard/src/pages/InstallmentPlanDetailPage.tsx`

### Imports
```tsx
import { useParams, useNavigate } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
import { installmentPlansApi } from '../api/client';
import { DataTable, ConfirmModal, PageLoader } from '../components';
import { getName, type InstallmentPlan, type Installment } from '../types';
import { useState } from 'react';
import { toast } from '../components/Toast';
```

### Components Used
```tsx
<DataTable
<ConfirmModal
return <PageLoader />;
```

### API Calls
```
queryFn: () => installmentPlansApi.get(planId),
mutationFn: (id: number) => installmentPlansApi.generate(id),
mutationFn: (id: number) => installmentPlansApi.delete(id),
```

### File Statistics
- Lines: 244
- Size: 9668 chars

---

## InstallmentPlanFormPage

**File:** `eduadmin-dashboard/src/pages/InstallmentPlanFormPage.tsx`

### Imports
```tsx
import { useState, useEffect } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Save, X, Plus, Trash2, AlertCircle, ArrowRight } from 'lucide-react';
import { installmentPlansApi, gradesApi, feesApi, studentsApi } from '../api/client';
import { PageLoader } from '../components';
import { getName, type InstallmentPlan, type Fee } from '../types';
import { toast } from '../components/Toast';
```

### Components Used
```tsx
return <PageLoader />;
```

### API Calls
```
queryFn: () => feesApi.list(),
queryFn: () => gradesApi.list(),
queryFn: () => studentsApi.getClassroomsByGrade(parseInt(formData.grade_id, 10)),
mutationFn: ({ id, data }: { id: number; data: any }) => installmentPlansApi.update(id, data),
queryFn: () => installmentPlansApi.get(planId),
mutationFn: (data: any) => installmentPlansApi.create(data),
```

### Filters / Search State
```tsx
const next = prev.filter((_, i) => i !== index);
```

### File Statistics
- Lines: 573
- Size: 24011 chars

---

## InstallmentPlansPage

**File:** `eduadmin-dashboard/src/pages/InstallmentPlansPage.tsx`

### Imports
```tsx
import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Plus, Pencil, Trash2, Eye, CreditCard } from 'lucide-react';
import { useNavigate } from 'react-router-dom';
import { installmentPlansApi } from '../api/client';
import { DataTable, ConfirmModal, PageLoader } from '../components';
import { getName, type InstallmentPlan } from '../types';
import { toast } from '../components/Toast';
```

### Components Used
```tsx
<DataTable
<ConfirmModal
return <PageLoader />;
```

### API Calls
```
queryFn: () => installmentPlansApi.list(),
mutationFn: (id: number) => installmentPlansApi.delete(id),
```

### File Statistics
- Lines: 177
- Size: 6173 chars

---

## IntegrationsPage

**File:** `eduadmin-dashboard/src/pages/IntegrationsPage.tsx`

### Imports
```tsx
import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Plus, Plug, Pencil, Trash2, Power } from 'lucide-react';
import { platformApi } from '../api/client';
import type { Integration } from '../types';
import { DataTable, EditModal, PageLoader, ConfirmModal } from '../components';
import { toast } from '../components/Toast';
```

### Components Used
```tsx
<ConfirmModal
<EditModal
if (isLoading) return <PageLoader />;
<DataTable
```

### API Calls
```
mutationFn: ({ id, data }: { id: number; data: any }) => platformApi.integrationsUpdate(id, data),
mutationFn: (id: number) => platformApi.integrationsToggle(id),
queryFn: platformApi.integrationsList,
mutationFn: platformApi.integrationsCreate,
mutationFn: (id: number) => platformApi.integrationsDelete(id),
```

### State / Modals
```tsx
isOpen={modalOpen}
isOpen={!!deleteTarget}
const [modalOpen, setModalOpen] = useState(false);
```

### File Statistics
- Lines: 184
- Size: 8788 chars

---

## LibraryPage

**File:** `eduadmin-dashboard/src/pages/LibraryPage.tsx`

### Imports
```tsx
import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
import { libraryApi, gradesApi, classroomsApi, sectionsApi, teachersApi } from '../api/client';
import { DataTable, ConfirmModal, PageLoader, EditModal } from '../components';
import {
import { toast } from '../components/Toast';
```

### Components Used
```tsx
if (itemsLoading) return <PageLoader />;
<EditModal
<ConfirmModal
<DataTable
```

### API Calls
```
queryFn: sectionsApi.list,
queryFn: teachersApi.list,
libraryApi.update(id, data),
mutationFn: libraryApi.delete,
queryFn: libraryApi.list,
queryFn: gradesApi.list,
mutationFn: libraryApi.create,
libraryApi.download(filename);
queryFn: classroomsApi.list,
```

### State / Modals
```tsx
isOpen={editModalOpen}
isOpen={showForm}
isOpen={deleteId !== null}
```

### File Statistics
- Lines: 517
- Size: 17853 chars

---

## LoginPage

**File:** `eduadmin-dashboard/src/pages/LoginPage.tsx`

### Imports
```tsx
import { useState } from 'react';
import { GraduationCap, Mail, Lock, Eye, EyeOff } from 'lucide-react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { useApiErrors } from '../lib/useApiErrors';
import { ErrorSummary, FieldError } from '../components/FormErrors';
import { toApiError } from '../lib/apiError';
import { toast } from '../components/Toast';
```

### API Calls
```
aria-describedby={apiErrors.get('email') ? 'email-error' : undefined}
apiErrors.get('email')
apiErrors.get('password')
aria-invalid={Boolean(apiErrors.get('password'))}
aria-describedby={apiErrors.get('password') ? 'password-error' : undefined}
aria-invalid={Boolean(apiErrors.get('email'))}
```

### File Statistics
- Lines: 237
- Size: 9258 chars

---

## MyNotificationsPage

**File:** `eduadmin-dashboard/src/pages/MyNotificationsPage.tsx`

### Imports
```tsx
import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useNavigate } from 'react-router-dom';
import {
import { notificationsApi } from '../api/client';
import { ConfirmModal, EditModal, PageLoader } from '../components';
import type { NotificationRecipient } from '../types';
import { getName } from '../types';
import { formatDateShort, formatDateTime } from '../lib/utils';
```

### Components Used
```tsx
<EditModal isOpen={true} title="تفاصيل الإشعار" onClose={() => setSelectedNotification(null)} hideFooter maxWidth="max-w-lg" onSave={() => {}}>
if (isLoading) return <PageLoader />;
<ConfirmModal
```

### API Calls
```
mutationFn: (id: number) => notificationsApi.markAsRead(id),
mutationFn: (id: number) => notificationsApi.deleteMyNotification(id),
queryFn: () => notificationsApi.myNotifications(activeTab === 'unread'),
mutationFn: () => notificationsApi.markAllAsRead(),
```

### State / Modals
```tsx
<EditModal isOpen={true} title="تفاصيل الإشعار" onClose={() => setSelectedNotification(null)} hideFooter maxWidth="max-w-lg" onSave={() => {}}>
isOpen={deleteId !== null}
```

### Filters / Search State
```tsx
const unreadCount = notifications?.filter((n) => !n.is_read).length || 0;
```

### File Statistics
- Lines: 306
- Size: 12986 chars

---

## NotificationsPage

**File:** `eduadmin-dashboard/src/pages/NotificationsPage.tsx`

### Imports
```tsx
import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useNavigate } from 'react-router-dom';
import {
import { notificationsApi, studentsApi, teachersApi, parentsApi } from '../api/client';
import { DataTable, ConfirmModal, PageLoader, EditModal } from '../components';
import type { Notification, SendNotificationPayload } from '../types';
import { getName } from '../types';
import { formatDate, formatDateTime } from '../lib/utils';
import { toast } from '../components/Toast';
```

### Components Used
```tsx
<EditModal isOpen={true} title="تفاصيل الإشعار" onClose={() => setShowDetailModal(false)} onSave={() => setShowDetailModal(false)} hideFooter maxWidth="max-w-2xl">
<ConfirmModal
if (isLoading) return <PageLoader />;
<DataTable
<EditModal
```

### API Calls
```
mutationFn: (payload: SendNotificationPayload) => notificationsApi.create(payload),
queryFn: () => parentsApi.list(),
queryFn: notificationsApi.list,
queryFn: () => teachersApi.list(),
queryFn: () => selectedNotification ? notificationsApi.get(selectedNotification.id) : null,
mutationFn: (id: number) => notificationsApi.delete(id),
queryFn: () => studentsApi.list(),
```

### State / Modals
```tsx
isOpen={showSendModal}
isOpen={deleteId !== null}
```

### Filters / Search State
```tsx
users = users.filter((u) => u.name.toLowerCase().includes(targetSearch.toLowerCase()));
value={searchTerm}
{filteredNotifications?.length || 0} إشعار
emptyMessage={searchTerm ? 'لا توجد نتائج مطابقة للبحث' : 'لا توجد إشعارات'}
setFormTargets(formTargets.filter((t) => !(t.id === id && t.type === type)));
const term = searchTerm.trim().toLowerCase();
data={filteredNotifications || []}
if (filterType !== 'all' && n.type !== filterType) return false;
const [filterType, setFilterType] = useState<string>('all');
value={filterType}
onChange={(e) => setSearchTerm(e.target.value)}
if (searchTerm) {
const [searchTerm, setSearchTerm] = useState('');
const filteredNotifications = notifications?.filter((n) => {
```

### File Statistics
- Lines: 674
- Size: 28756 chars

---

## OnlineClassesPage

**File:** `eduadmin-dashboard/src/pages/OnlineClassesPage.tsx`

### Imports
```tsx
import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
import {
import { DataTable, ConfirmModal, PageLoader, EditModal } from '../components';
import { getName, type Grade, type Classroom, type Section } from '../types';
import { getChangedFields } from '../lib/diff';
import { toast } from '../components/Toast';
```

### Components Used
```tsx
<DataTable columns={columns} data={classes} />
<EditModal
if (isLoading) return <PageLoader />;
<ConfirmModal
```

### API Calls
```
queryFn: sectionsApi.list,
queryFn: onlineClassesApi.list,
onlineClassesApi.update(id, data),
mutationFn: onlineClassesApi.create,
queryFn: gradesApi.list,
mutationFn: onlineClassesApi.delete,
queryFn: classroomsApi.list,
```

### State / Modals
```tsx
isOpen={editModalOpen}
isOpen={showForm}
isOpen={deleteId !== null}
```

### File Statistics
- Lines: 704
- Size: 25401 chars

---

## PaperExamMarksPage

**File:** `eduadmin-dashboard/src/pages/PaperExamMarksPage.tsx`

### Imports
```tsx
import { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { ArrowRight, Save, Send } from 'lucide-react';
import { paperExamsApi } from '../api/client';
import { getName } from '../types';
import { PageLoader } from '../components';
import { toast } from '../components/Toast';
```

### Components Used
```tsx
if (isLoading || studentsLoading) return <PageLoader />;
```

### API Calls
```
mutationFn: () => paperExamsApi.publish(examId),
queryFn: () => paperExamsApi.getStudents(examId),
paperExamsApi.saveMarks(examId, marksData),
queryFn: () => paperExamsApi.get(examId),
```

### Filters / Search State
```tsx
.filter(([, v]) => v.marks_obtained !== '')
```

### File Statistics
- Lines: 182
- Size: 8374 chars

---

## PaperExamsPage

**File:** `eduadmin-dashboard/src/pages/PaperExamsPage.tsx`

### Imports
```tsx
import { useState, useEffect } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useNavigate } from 'react-router-dom';
import { Plus, Pencil, Trash2, ClipboardCheck, Send } from 'lucide-react';
import { paperExamsApi, subjectsApi, gradesApi, teachersApi, sectionsApi, isDeferredAction } from '../api/client';
import { useClassroomsByGrade, useSectionsByClassroom } from '../hooks/useDependentOptions';
import { getName } from '../types';
import { EXAM_TYPES, getExamTypeLabel } from '../lib/examTypes';
import { ConfirmModal, EditModal, PageLoader } from '../components';
import { toast } from '../components/Toast';
```

### Components Used
```tsx
<EditModal
if (isLoading) return <PageLoader />;
<ConfirmModal
<EditModal isOpen={true} title="تعديل الاختبار" onClose={() => setEditModalOpen(false)} onSave={handleEditSubmit} isLoading={updateMutation.isPending} maxWidth="max-w-2xl">
```

### API Calls
```
const { data: grades } = useQuery({ queryKey: ['grades'], queryFn: () => gradesApi.list() });
const { data: subjects } = useQuery({ queryKey: ['subjects'], queryFn: () => subjectsApi.list() });
const { data: sections } = useQuery({ queryKey: ['sections'], queryFn: () => sectionsApi.list() });
const { data: teachers } = useQuery({ queryKey: ['teachers'], queryFn: () => teachersApi.list() });
mutationFn: (id: number) => paperExamsApi.delete(id),
queryFn: () => paperExamsApi.list(),
mutationFn: (id: number) => paperExamsApi.publish(id),
mutationFn: (data: PaperExamFormData) => paperExamsApi.create(data),
mutationFn: ({ id, data }: { id: number; data: any }) => paperExamsApi.update(id, data),
```

### State / Modals
```tsx
isOpen={showForm}
isOpen={deleteId !== null}
```

### File Statistics
- Lines: 347
- Size: 18496 chars

---

## ParentDashboardPage

**File:** `eduadmin-dashboard/src/pages/ParentDashboardPage.tsx`

### Imports
```tsx
import { useQuery } from '@tanstack/react-query';
import {
import { parentDashboardApi } from '../api/client';
import { type ParentDashboardData } from '../types';
import { PageLoader } from '../components';
```

### Components Used
```tsx
if (isLoading) return <PageLoader />;
```

### API Calls
```
queryFn: parentDashboardApi.show,
```

### Filters / Search State
```tsx
{[child.grade_name, child.classroom_name, child.section_name].filter(Boolean).join(' — ')}
```

### File Statistics
- Lines: 292
- Size: 14963 chars

---

## ParentDetailPage

**File:** `eduadmin-dashboard/src/pages/ParentDetailPage.tsx`

### Imports
```tsx
import { useParams, useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import {
import { parentsApi } from '../api/client';
import { getName, type LocalizedName } from '../types';
import { PageLoader } from '../components';
import { SplitTabLayout } from '../components/SplitTabLayout';
```

### Components Used
```tsx
<PageLoader />
<SplitTabLayout
```

### API Calls
```
queryFn: () => parentsApi.get(parentId),
```

### File Statistics
- Lines: 370
- Size: 14549 chars

---

## ParentsPage

**File:** `eduadmin-dashboard/src/pages/ParentsPage.tsx`

### Imports
```tsx
import { useState, useMemo } from 'react';
import { useNavigate } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Plus, Pencil, Trash2, Users, X, Search, Eye } from 'lucide-react';
import { parentsApi } from '../api/client';
import { getName, type LocalizedName } from '../types';
import { EditModal, ConfirmModal } from '../components';
import { getChangedFields } from '../lib/diff';
import { toast } from '../components/Toast';
```

### Components Used
```tsx
<EditModal
<ConfirmModal
```

### API Calls
```
parentsApi.update(id, data),
queryFn: () => parentsApi.list(),
mutationFn: (data: ParentFormData) => parentsApi.create(data),
mutationFn: (id: number) => parentsApi.delete(id),
```

### State / Modals
```tsx
isOpen={editModalOpen}
isOpen={showForm}
isOpen={deleteId !== null}
```

### Filters / Search State
```tsx
{filteredParents.map((parent: Parent, index: number) => (
value={searchTerm}
return (parents as Parent[]).filter((parent: Parent) => {
{filteredParents.length} ولي أمر
const term = searchTerm.trim().toLowerCase();
}, [parents, searchTerm]);
const filteredParents = useMemo(() => {
onChange={(e) => setSearchTerm(e.target.value)}
{searchTerm ? 'لا توجد نتائج مطابقة للبحث' : 'لا يوجد أولياء أمور مسجلين حالياً'}
const [searchTerm, setSearchTerm] = useState('');
{filteredParents.length === 0 && (
```

### File Statistics
- Lines: 466
- Size: 19895 chars

---

## PaymentsPage

**File:** `eduadmin-dashboard/src/pages/PaymentsPage.tsx`

### Imports
```tsx
import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Plus, Pencil, Trash2, CreditCard } from 'lucide-react';
import { paymentsApi, studentsApi } from '../api/client';
import { DataTable, ConfirmModal, PageLoader } from '../components';
import { type LocalizedName, getName } from '../types';
import { toast } from '../components/Toast';
```

### Components Used
```tsx
<DataTable
<ConfirmModal
return <PageLoader />;
```

### API Calls
```
mutationFn: (id: number) => paymentsApi.delete(id),
paymentsApi.create({
queryFn: () => paymentsApi.list(),
paymentsApi.update(id, {
queryFn: () => studentsApi.list(),
```

### File Statistics
- Lines: 377
- Size: 13624 chars

---

## PendingRequestsPage

**File:** `eduadmin-dashboard/src/pages/PendingRequestsPage.tsx`

### Imports
```tsx
import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
import { pendingRequestsApi } from '../api/client';
import { PageLoader } from '../components';
import { ConfirmModal } from '../components/ConfirmModal';
import { PendingRequestActions } from '../components/PendingRequestActions';
import { PendingRequestPreviewModal } from '../components/PendingRequestPreviewModal';
import { toast } from '../components/Toast';
import { formatDateTime, cn } from '../lib/utils';
import {
import type { PendingRequest, PendingRequestStatus } from '../types';
```

### Components Used
```tsx
<PageLoader />
<ConfirmModal
```

### API Calls
```
mutationFn: (id: number) => pendingRequestsApi.cancel(id),
queryFn: pendingRequestsApi.stats,
queryFn: pendingRequestsApi.actions,
pendingRequestsApi.list({
```

### State / Modals
```tsx
isOpen={Boolean(cancelling)}
```

### File Statistics
- Lines: 340
- Size: 14631 chars

---

## PermissionTemplatesPage

**File:** `eduadmin-dashboard/src/pages/PermissionTemplatesPage.tsx`

### Imports
```tsx
import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Plus, ShieldCheck, Pencil, Trash2 } from 'lucide-react';
import { platformApi } from '../api/client';
import { type PermissionTemplate, getName } from '../types';
import { DataTable, EditModal, PageLoader, ConfirmModal } from '../components';
import { toast } from '../components/Toast';
```

### Components Used
```tsx
<ConfirmModal
<EditModal
if (isLoading) return <PageLoader />;
<DataTable
```

### API Calls
```
mutationFn: platformApi.permissionTemplatesCreate,
mutationFn: ({ id, data }: { id: number; data: any }) => platformApi.permissionTemplatesUpdate(id, data),
mutationFn: (id: number) => platformApi.permissionTemplatesDelete(id),
queryFn: platformApi.permissionTemplatesList,
```

### State / Modals
```tsx
isOpen={modalOpen}
isOpen={!!deleteTarget}
const [modalOpen, setModalOpen] = useState(false);
```

### Filters / Search State
```tsx
render: (row: PermissionTemplate) => <span className="text-gray-700">{Object.values(row.permissions ?? {}).filter(Boolean).length}</span>,
```

### File Statistics
- Lines: 188
- Size: 9178 chars

---

## PlansPage

**File:** `eduadmin-dashboard/src/pages/PlansPage.tsx`

### Imports
```tsx
import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Plus, Layers, Pencil, Trash2 } from 'lucide-react';
import { platformApi } from '../api/client';
import { type Plan, getName, type LocalizedName } from '../types';
import { DataTable, EditModal, PageLoader, ConfirmModal } from '../components';
import { toast } from '../components/Toast';
```

### Components Used
```tsx
<ConfirmModal
<EditModal
if (isLoading) return <PageLoader />;
<DataTable
```

### API Calls
```
mutationFn: (id: number) => platformApi.plansDelete(id),
mutationFn: ({ id, data }: { id: number; data: any }) => platformApi.plansUpdate(id, data),
mutationFn: platformApi.plansCreate,
queryFn: platformApi.plansList,
```

### State / Modals
```tsx
isOpen={modalOpen}
isOpen={!!deleteTarget}
const [modalOpen, setModalOpen] = useState(false);
```

### File Statistics
- Lines: 229
- Size: 11703 chars

---

## PlatformAdminsPage

**File:** `eduadmin-dashboard/src/pages/PlatformAdminsPage.tsx`

### Imports
```tsx
import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Plus, ShieldCheck, Pencil, Trash2 } from 'lucide-react';
import { platformApi } from '../api/client';
import type { PlatformAdmin } from '../types';
import { DataTable, EditModal, PageLoader, ConfirmModal } from '../components';
import { useAuth } from '../context/AuthContext';
import { toast } from '../components/Toast';
```

### Components Used
```tsx
<ConfirmModal
<EditModal
if (isLoading) return <PageLoader />;
<DataTable
```

### API Calls
```
queryFn: platformApi.adminsList,
mutationFn: platformApi.adminsCreate,
mutationFn: (id: number) => platformApi.adminsDelete(id),
mutationFn: ({ id, data }: { id: number; data: any }) => platformApi.adminsUpdate(id, data),
```

### State / Modals
```tsx
isOpen={modalOpen}
isOpen={!!deleteTarget}
const [modalOpen, setModalOpen] = useState(false);
```

### File Statistics
- Lines: 178
- Size: 8264 chars

---

## PlatformDashboardPage

**File:** `eduadmin-dashboard/src/pages/PlatformDashboardPage.tsx`

### Imports
```tsx
import { useQuery } from '@tanstack/react-query';
import {
import { platformApi } from '../api/client';
import { PageLoader } from '../components';
import type { PlatformDashboardStats } from '../types';
import { cn } from '../lib/utils';
```

### Components Used
```tsx
if (isLoading) return <PageLoader />;
```

### API Calls
```
queryFn: platformApi.dashboard,
```

### File Statistics
- Lines: 218
- Size: 10254 chars

---

## PlatformSettingsPage

**File:** `eduadmin-dashboard/src/pages/PlatformSettingsPage.tsx`

### Imports
```tsx
import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Save, Settings as SettingsIcon } from 'lucide-react';
import { platformApi } from '../api/client';
import type { PlatformSetting } from '../types';
import { PageLoader } from '../components';
import { toast } from '../components/Toast';
import { toApiError } from '../lib/apiError';
```

### Components Used
```tsx
if (isLoading) return <PageLoader />;
```

### API Calls
```
mutationFn: platformApi.settingsUpsert,
queryFn: platformApi.settingsList,
```

### File Statistics
- Lines: 145
- Size: 6988 chars

---

## ProcessingFeePage

**File:** `eduadmin-dashboard/src/pages/ProcessingFeePage.tsx`

### Imports
```tsx
import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Plus, Pencil, Trash2, Settings2 } from 'lucide-react';
import { processingFeeApi, studentsApi } from '../api/client';
import { DataTable, ConfirmModal, PageLoader } from '../components';
import { getName } from '../types';
import { toast } from '../components/Toast';
```

### Components Used
```tsx
<DataTable
<ConfirmModal
return <PageLoader />;
```

### API Calls
```
processingFeeApi.create({
processingFeeApi.update(id, {
queryFn: () => processingFeeApi.list(),
mutationFn: (id: number) => processingFeeApi.delete(id),
queryFn: () => studentsApi.list(),
```

### File Statistics
- Lines: 377
- Size: 13735 chars

---

## ProfitLossPage

**File:** `eduadmin-dashboard/src/pages/ProfitLossPage.tsx`

### Imports
```tsx
import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import {
import {
import { accountingApi } from '../api/client';
import { PageLoader } from '../components';
import { cn } from '../lib/utils';
import { getName, type AccountingSummary, type ProfitLossReport } from '../types';
```

### Components Used
```tsx
return <PageLoader />;
```

### API Calls
```
queryFn: () => accountingApi.profitLoss(params),
queryFn: () => accountingApi.summary(params),
```

### File Statistics
- Lines: 386
- Size: 14211 chars

---

## PromotionPage

**File:** `eduadmin-dashboard/src/pages/PromotionPage.tsx`

### Imports
```tsx
import { useState, useMemo } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
import {
import { DataTable, ConfirmModal, PageLoader } from '../components';
import { getName, type Grade, type Classroom, type Section } from '../types';
import { toast } from '../components/Toast';
```

### Components Used
```tsx
<DataTable columns={promotedColumns} data={promotedList} />
<ConfirmModal
if (promotedLoading) return <PageLoader />;
```

### API Calls
```
queryFn: sectionsApi.list,
mutationFn: promotionApi.getStudentsForPromotion,
mutationFn: promotionApi.delete,
queryFn: gradesApi.list,
mutationFn: promotionApi.bulkPromote,
queryFn: promotionApi.list,
next.delete(classroomId);
queryFn: classroomsApi.list,
next.delete(gradeId);
```

### State / Modals
```tsx
isOpen={revertId !== null}
```

### Filters / Search State
```tsx
{filteredFromSections.map((s: Section) => (
sections: sections.filter((s: Section) => s.Class_id === classroom.id),
const filteredFromClassrooms = useMemo(() => {
const selectedIds = students.filter((s) => s.selected).map((s) => s.id);
const filteredFromSections = useMemo(() => {
return classrooms.filter((c: Classroom) => c.Grade_id === Number(fromGradeId));
.filter((c: Classroom) => c.Grade_id === grade.id)
return sections.filter((s: Section) => s.Class_id === Number(fromClassroomId));
const selectedCount = students.filter((s) => s.selected).length;
{filteredFromClassrooms.map((c: Classroom) => (
```

### File Statistics
- Lines: 713
- Size: 28108 chars

---

## QuestionsPage

**File:** `eduadmin-dashboard/src/pages/QuestionsPage.tsx`

### Imports
```tsx
import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Plus, Pencil, Trash2, FileQuestion, X, CircleHelp, ListPlus, Minus } from 'lucide-react';
import { questionsApi, quizzesApi } from '../api/client';
import { type LocalizedName, getName } from '../types';
import { EditModal, ConfirmModal, PageLoader } from '../components';
import { getChangedFields } from '../lib/diff';
import { toast } from '../components/Toast';
```

### Components Used
```tsx
<PageLoader />
<EditModal
<ConfirmModal
```

### API Calls
```
mutationFn: (data: QuestionFormData) => questionsApi.create(data),
questionsApi.bulkCreate(data),
queryFn: () => quizzesApi.list(),
mutationFn: (id: number) => questionsApi.delete(id),
questionsApi.update(id, data),
queryFn: () => questionsApi.list(),
```

### State / Modals
```tsx
isOpen={editModalOpen}
isOpen={showForm}
isOpen={deleteId !== null}
```

### Filters / Search State
```tsx
const validItems = bulkItems.filter(
return answers.split(',').map((a) => a.trim()).filter(Boolean);
setBulkItems(bulkItems.filter((item) => item.id !== id));
```

### File Statistics
- Lines: 710
- Size: 29250 chars

---

## QuizzesPage

**File:** `eduadmin-dashboard/src/pages/QuizzesPage.tsx`

### Imports
```tsx
import { useState, useEffect, useMemo } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Plus, Pencil, Trash2, ClipboardCheck, X } from 'lucide-react';
import {
import { useClassroomsByGrade, useSectionsByClassroom } from '../hooks/useDependentOptions';
import { type LocalizedName, getName } from '../types';
import { EditModal, ConfirmModal, PageLoader } from '../components';
import { getChangedFields } from '../lib/diff';
import { toast } from '../components/Toast';
import { useAcademicYear } from '../context/AcademicYearContext';
```

### Components Used
```tsx
<PageLoader />
<EditModal
<ConfirmModal
```

### API Calls
```
mutationFn: (id: number) => quizzesApi.delete(id),
mutationFn: (data: QuizFormData) => quizzesApi.create(data),
queryFn: () => gradesApi.list(),
queryFn: () => subjectsApi.list(),
queryFn: () => classroomsApi.list(),
queryFn: () => teachersApi.list(),
queryFn: () => quizzesApi.list({ academic_year_id: selectedYearId ?? undefined }),
queryFn: () => sectionsApi.list(),
quizzesApi.update(id, data),
```

### State / Modals
```tsx
isOpen={editModalOpen}
isOpen={showForm}
isOpen={deleteId !== null}
```

### Filters / Search State
```tsx
{filteredSubjects.map((subject: Subject) => (
return subjects.filter((s: Subject) => {
{filteredTeachers.map((teacher: Teacher) => (
return teachers.filter((t: Teacher) => t.id === subjectTeacherId);
const filteredSubjects = useMemo(() => {
const filteredTeachers = useMemo(() => {
sections?.filter(
```

### File Statistics
- Lines: 735
- Size: 29262 chars

---

## ReceiptsPage

**File:** `eduadmin-dashboard/src/pages/ReceiptsPage.tsx`

### Imports
```tsx
import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Plus, Pencil, Trash2, Receipt } from 'lucide-react';
import { receiptsApi, studentsApi } from '../api/client';
import { DataTable, ConfirmModal, PageLoader } from '../components';
import { getName } from '../types';
import { toast } from '../components/Toast';
```

### Components Used
```tsx
<DataTable
<ConfirmModal
return <PageLoader />;
```

### API Calls
```
receiptsApi.update(id, {
mutationFn: (id: number) => receiptsApi.delete(id),
receiptsApi.create({
queryFn: () => studentsApi.list(),
queryFn: () => receiptsApi.list(),
```

### File Statistics
- Lines: 382
- Size: 13657 chars

---

## RewardsPenaltiesPage

**File:** `eduadmin-dashboard/src/pages/RewardsPenaltiesPage.tsx`

### Imports
```tsx
import { useMemo, useState } from 'react';
import { useLocation } from 'react-router-dom';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import {
import { rewardsPenaltiesApi, studentsApi, gradesApi, classroomsApi, sectionsApi } from '../api/client';
import { getName } from '../types';
import type {
import { PageLoader, EditModal, ConfirmModal } from '../components';
import { useAuth } from '../context/AuthContext';
import { toast } from '../components/Toast';
```

### Components Used
```tsx
<PageLoader />
<EditModal
if (isLoading) return <PageLoader />;
<ConfirmModal
```

### API Calls
```
return rewardsPenaltiesApi.update(editing.id, update);
const { data: grades } = useQuery<Grade[]>({ queryKey: ['grades'], queryFn: () => gradesApi.list() as Promise<Grade[]> });
const { data: allClassrooms } = useQuery<Classroom[]>({ queryKey: ['classrooms'], queryFn: () => classroomsApi.list() as Promise<Classroom[]> });
queryFn: () => rewardsPenaltiesApi.list({ ...filterParams, page, per_page: 15 }),
queryFn: () => rewardsPenaltiesApi.stats(filterParams),
queryFn: () => studentsApi.list() as Promise<TransportStudent[]>,
mutationFn: (id: number) => rewardsPenaltiesApi.remove(id),
mutationFn: () => rewardsPenaltiesApi.updateSettings({
const { data: allSections } = useQuery<Section[]>({ queryKey: ['sections'], queryFn: () => sectionsApi.list() as Promise<Section[]> });
return rewardsPenaltiesApi.create(payload);
queryFn: () => rewardsPenaltiesApi.suggestions({
queryFn: () => rewardsPenaltiesApi.settings(),
rewardsPenaltiesApi.applySuggestions(items.map((s) => ({
```

### State / Modals
```tsx
isOpen={modalOpen}
isOpen={!!deleteTarget}
const [modalOpen, setModalOpen] = useState(false);
```

### Filters / Search State
```tsx
setSearch('');
return list.filter((r) => getName(r.student?.name || '').toLowerCase().includes(q) || r.title.toLowerCase().includes(q));
queryFn: () => rewardsPenaltiesApi.list({ ...filterParams, page, per_page: 15 }),
const filterParams = useMemo(() => ({
queryFn: () => rewardsPenaltiesApi.stats(filterParams),
() => (allSections ?? []).filter((s) => {
queryKey: ['rewards-penalties-stats', filterParams],
onChange={(e) => setSearch(e.target.value)}
() => (allClassrooms ?? []).filter((c) => !gradeId || c.Grade_id === Number(gradeId)),
queryKey: ['rewards-penalties', { ...filterParams, page }],
{/* Hierarchical filters */}
const [search, setSearch] = useState('');
```

### Permissions
```tsx
const canManageSettings = hasPermission('rewards.settings.manage');
const { hasPermission } = useAuth();
const canManage = hasPermission('rewards.manage');
```

### File Statistics
- Lines: 678
- Size: 31972 chars

---

## RolesPage

**File:** `eduadmin-dashboard/src/pages/RolesPage.tsx`

### Imports
```tsx
import { useMemo, useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Plus, KeyRound, ShieldCheck, Pencil, Trash2, UserCog, Users, UserPlus, GraduationCap, School as SchoolIcon } from 'lucide-react';
import { rolesApi, schoolsApi } from '../api/client';
import {
import { DataTable, EditModal, PageLoader, ConfirmModal } from '../components';
import { useAuth } from '../context/AuthContext';
import { cn } from '../lib/utils';
import { toast } from '../components/Toast';
```

### Components Used
```tsx
<PageLoader />
<EditModal
<ConfirmModal
<DataTable
```

### API Calls
```
mutationFn: ({ id, type, roleIds }: { id: number; type?: 'user' | 'teacher'; roleIds: number[] }) => rolesApi.assignRoles(id, { role_ids: roleIds, type }),
mutationFn: (data: any) => rolesApi.create({ ...data, school_id: schoolId ?? undefined }),
queryFn: () => rolesApi.list(schoolId ? { school_id: schoolId } : undefined),
rolesApi.createUser({ ...data, school_id: schoolId ?? undefined }),
queryFn: () => rolesApi.users(schoolId ? { school_id: schoolId } : undefined),
mutationFn: (id: number) => rolesApi.delete(id),
queryFn: schoolsApi.list,
queryFn: () => rolesApi.permissions(schoolId ? { school_id: schoolId } : undefined),
mutationFn: ({ id, data }: { id: number; data: any }) => rolesApi.update(id, data),
```

### State / Modals
```tsx
isOpen={roleModalOpen}
isOpen={!!deleteTarget}
isOpen={!!assignTarget}
isOpen={createUserOpen}
```

### Filters / Search State
```tsx
setAssignedIds((ids) => e.target.checked ? [...ids, r.id] : ids.filter((i) => i !== r.id))
<span className="ms-auto text-xs text-gray-400">{Object.values(r.permissions ?? {}).filter(Boolean).length} صلاحية</span>
render: (row: Role) => <span className="text-gray-700">{Object.values(row.permissions ?? {}).filter(Boolean).length}</span>,
(roles as Role[]).filter(r => r.is_active).map((r) => (
onChange={(e) => setUserForm(f => ({ ...f, role_ids: e.target.checked ? [...f.role_ids, r.id] : f.role_ids.filter(i => i !== r.id) }))}
```

### File Statistics
- Lines: 516
- Size: 24311 chars

---

## SalariesPage

**File:** `eduadmin-dashboard/src/pages/SalariesPage.tsx`

### Imports
```tsx
import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Plus, Pencil, Trash2, Wallet, CheckCircle2, CalendarClock, AlertCircle, Banknote } from 'lucide-react';
import { salariesApi, accountingApi, isDeferredAction } from '../api/client';
import { DataTable, ConfirmModal, EditModal, PageLoader } from '../components';
import { getName, type SalaryPayment, type SalaryEmployee, type UpcomingSalary } from '../types';
import { useAuth } from '../context/AuthContext';
import { toast } from '../components/Toast';
```

### Components Used
```tsx
<DataTable
<EditModal
<ConfirmModal
return <PageLoader />;
```

### API Calls
```
queryFn: () => salariesApi.list(params),
mutationFn: (id: number) => salariesApi.delete(id),
queryFn: () => accountingApi.upcomingSalaries(),
queryFn: () => accountingApi.summary({ year: Number(filterYear) }),
salariesApi.update(id, {
queryFn: () => salariesApi.employees(),
salariesApi.pay(id, {
salariesApi.create({
salariesApi.generate({
```

### State / Modals
```tsx
isOpen={showSalaryModal}
isOpen={!!payTarget}
isOpen={!!editTarget}
isOpen={showGenerate}
```

### Filters / Search State
```tsx
value={filterYear}
queryKey: ['accounting-summary', filterYear],
queryFn: () => accountingApi.summary({ year: Number(filterYear) }),
const [filterStatus, setFilterStatus] = useState('');
status: filterStatus || undefined,
const [filterYear, setFilterYear] = useState(String(CURRENT_YEAR));
// filters
period_month: filterMonth ? Number(filterMonth) : undefined,
employee_id: filterEmployee ? Number(filterEmployee.split(':')[1]) : undefined,
value={filterMonth}
value={filterStatus}
const overdueCount = (upcoming || []).filter((u: UpcomingSalary) => u.is_overdue).length;
const [filterMonth, setFilterMonth] = useState('');
.filter((u: UpcomingSalary) => u.is_overdue)
?.filter((emp: SalaryEmployee) => emp.employee_type === manualForm.employee_type)
value={filterEmployee}
const [filterEmployee, setFilterEmployee] = useState('');
period_year: filterYear ? Number(filterYear) : undefined,
{(filterEmployee || filterYear || filterMonth || filterStatus) && (
employee_type: filterEmployee ? filterEmployee.split(':')[0] : undefined,
```

### Permissions
```tsx
const { hasPermission } = useAuth();
const canManage = hasPermission('salaries.manage');
```

### File Statistics
- Lines: 853
- Size: 35752 chars

---

## SchedulePeriodsPage

**File:** `eduadmin-dashboard/src/pages/SchedulePeriodsPage.tsx`

### Imports
```tsx
import { useEffect, useMemo, useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Clock, Plus, Trash2, Save, Copy, RotateCcw } from 'lucide-react';
import { academicYearsApi, schedulePeriodsApi } from '../api/client';
import { getName, type AcademicYear, type SchedulePeriod } from '../types';
import { useAuth } from '../context/AuthContext';
import { PageLoader } from '../components';
import { toast } from '../components/Toast';
```

### Components Used
```tsx
<PageLoader message="جاري تحميل الفترات..." />
if (loadingYears) return <PageLoader message="جاري التحميل..." />;
```

### API Calls
```
schedulePeriodsApi.update(Number(effectiveYearId), periodsPayload),
queryFn: () => schedulePeriodsApi.byYear(Number(effectiveYearId)),
queryFn: () => academicYearsApi.list(),
```

### Filters / Search State
```tsx
setRows([...updated.map((r) => ({ ...r, day_of_week: null })), ...rows.filter((r) => r.day_of_week !== null)]);
setRows([...rows.filter((r) => r.day_of_week !== selectedDay), ...globals]);
const globals = rows.filter((r) => r.day_of_week === null).map((r) => ({ ...r, day_of_week: selectedDay as number }));
setRows(rows.filter((r) => r.day_of_week !== selectedDay));
setRows([...rows.filter((r) => r.day_of_week !== selectedDay), ...renumbered]);
const globals = rows.filter((r) => r.day_of_week === null);
if (selectedDay === 'all') return rows.filter((r) => r.day_of_week === null);
commitDisplayed(displayedRows.filter((_, i) => i !== index));
const dayRows = rows.filter((r) => r.day_of_week === selectedDay);
```

### Permissions
```tsx
const { hasPermission } = useAuth();
const canManage = hasPermission('academic_years.manage');
```

### File Statistics
- Lines: 368
- Size: 15333 chars

---

## SchoolsPage

**File:** `eduadmin-dashboard/src/pages/SchoolsPage.tsx`

### Imports
```tsx
import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
import { schoolsApi } from '../api/client';
import { type SchoolRecord, type SchoolTheme, getName } from '../types';
import { DataTable, EditModal, PageLoader, ConfirmModal } from '../components';
import { useTheme } from '../context/ThemeContext';
import { hexToRgba, mixHex } from '../lib/theme';
import { toast } from '../components/Toast';
```

### Components Used
```tsx
<ConfirmModal
<EditModal
if (isLoading) return <PageLoader />;
<DataTable
```

### API Calls
```
activate: () => schoolsApi.activate(id),
queryFn: () => schoolsApi.list({
mutationFn: ({ id, data }: { id: number; data: any }) => schoolsApi.update(id, data),
suspend: () => schoolsApi.suspend(id),
destroy: () => schoolsApi.destroy(id),
mutationFn: schoolsApi.create,
mutationFn: ({ id, data }: { id: number; data: FormData }) => schoolsApi.updateBranding(id, data),
restore: () => schoolsApi.restore(id),
archive: () => schoolsApi.archive(id),
```

### State / Modals
```tsx
isOpen={modalOpen}
isOpen={!!deleteTarget}
isOpen={brandingModalOpen}
const [modalOpen, setModalOpen] = useState(false);
```

### Filters / Search State
```tsx
onChange={(e) => setSearchInput(e.target.value)}
const [searchInput, setSearchInput] = useState('');
data={(filtered as any) || []}
<span className="text-sm text-gray-500">{filtered?.length ?? 0} مدرسة</span>
const filtered = (schools as SchoolRecord[] | undefined)?.filter((s) => {
```

### File Statistics
- Lines: 709
- Size: 32064 chars

---

## SectionSubjectTeachersPage

**File:** `eduadmin-dashboard/src/pages/SectionSubjectTeachersPage.tsx`

### Imports
```tsx
import { useMemo, useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { UserCog, Save, Trash2, School } from 'lucide-react';
import {
import { type Grade, type Classroom, type Section, type Subject, type Teacher, type SectionSubjectTeacher, getName } from '../types';
import { PageLoader, ConfirmModal } from '../components';
```

### Components Used
```tsx
if (loading) return <PageLoader />;
<ConfirmModal
```

### API Calls
```
mutationFn: sectionSubjectTeacherApi.remove,
sectionSubjectTeacherApi.list({
const { data: classrooms, isLoading: loadingClassrooms } = useQuery({ queryKey: ['classrooms'], queryFn: classroomsApi.list });
mutationFn: sectionSubjectTeacherApi.create,
const { data: subjects, isLoading: loadingSubjects } = useQuery({ queryKey: ['subjects'], queryFn: subjectsApi.list });
const { data: sections, isLoading: loadingSections } = useQuery({ queryKey: ['sections'], queryFn: sectionsApi.list });
const { data: grades, isLoading: loadingGrades } = useQuery({ queryKey: ['grades'], queryFn: gradesApi.list });
const { data: teachers, isLoading: loadingTeachers } = useQuery({ queryKey: ['teachers'], queryFn: teachersApi.list });
```

### State / Modals
```tsx
isOpen={deleteId !== null}
```

### Filters / Search State
```tsx
if (classroomId) list = list.filter((s) => s.Class_id === Number(classroomId));
const filteredSections = useMemo(() => {
let list = gradeId ? sectionList.filter((s) => s.Grade_id === Number(gradeId)) : sectionList;
{filteredSubjects.map((s) => (
() => (gradeId ? classroomList.filter((c) => c.Grade_id === Number(gradeId)) : classroomList),
return subjectList.filter((s) => !s.Grade_id || s.Grade_id === sec.Grade_id);
{filteredClassrooms.map((c) => (
const filteredClassrooms = useMemo(
const filteredSubjects = useMemo(() => {
{filteredSections.map((s) => (
```

### File Statistics
- Lines: 281
- Size: 12131 chars

---

## SectionsPage

**File:** `eduadmin-dashboard/src/pages/SectionsPage.tsx`

### Imports
```tsx
import { useState, useMemo } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Plus, Pencil, Trash2, Users, ToggleLeft, ToggleRight, X, Search } from 'lucide-react';
import { sectionsApi, gradesApi, classroomsApi, teachersApi } from '../api/client';
import { type Section, type Grade, type Classroom, type Teacher, getName } from '../types';
import { DataTable, ConfirmModal, PageLoader, EditModal } from '../components';
import { getChangedFields } from '../lib/diff';
import { toast } from '../components/Toast';
```

### Components Used
```tsx
<ConfirmModal
<EditModal
if (isLoading) return <PageLoader />;
<DataTable
```

### API Calls
```
queryFn: sectionsApi.list,
return classroomsApi.list();
queryFn: teachersApi.list,
return classroomsApi.filterByGrade(Number(selectedGradeId));
sectionsApi.update(id, { Status: status === 'active' ? 1 : 2 }),
mutationFn: ({ id, data }: { id: number; data: Partial<Section> }) => sectionsApi.update(id, data),
mutationFn: sectionsApi.create,
queryFn: gradesApi.list,
mutationFn: sectionsApi.delete,
queryFn: classroomsApi.list,
```

### State / Modals
```tsx
isOpen={editModalOpen}
isOpen={deleteId !== null}
```

### Filters / Search State
```tsx
const filteredSections = useMemo(() => {
return classroomsApi.filterByGrade(Number(selectedGradeId));
if (selectedGradeId) {
const term = searchTerm.trim().toLowerCase();
<span className="text-sm text-gray-500">{filteredSections?.length ?? 0} شعبة</span>
{filteredClassrooms.map((classroom: Classroom) => (
teacher_ids: prev.teacher_ids.filter((id) => id !== teacherId),
}, [sections, searchTerm, grades, allClassrooms, teachers]);
const [selectedGradeId, setSelectedGradeId] = useState('');
onChange={(e) => setSearchTerm(e.target.value)}
data={(filteredSections as any) || []}
queryKey: ['classrooms', selectedGradeId],
const [searchTerm, setSearchTerm] = useState('');
.filter(Boolean);
value={searchTerm}
emptyMessage={searchTerm ? 'لا توجد نتائج مطابقة للبحث' : 'لا توجد شعب دراسية'}
?.filter((teacher: Teacher) => !editForm.teacher_ids.includes(teacher.id))
return (sections as Section[] | undefined)?.filter((s) => {
const filteredClassrooms = selectedGradeId
?.filter((teacher: Teacher) => !addForm.teacher_ids.includes(teacher.id))
```

### File Statistics
- Lines: 707
- Size: 28043 chars

---

## SecurityPoliciesPage

**File:** `eduadmin-dashboard/src/pages/SecurityPoliciesPage.tsx`

### Imports
```tsx
import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Plus, Lock, Pencil, Trash2, Power } from 'lucide-react';
import { platformApi } from '../api/client';
import type { SecurityPolicy } from '../types';
import { DataTable, EditModal, PageLoader, ConfirmModal } from '../components';
import { toast } from '../components/Toast';
```

### Components Used
```tsx
<ConfirmModal
<EditModal
if (isLoading) return <PageLoader />;
<DataTable
```

### API Calls
```
mutationFn: (id: number) => platformApi.securityPoliciesToggle(id),
queryFn: platformApi.securityPoliciesList,
mutationFn: (id: number) => platformApi.securityPoliciesDelete(id),
mutationFn: platformApi.securityPoliciesCreate,
mutationFn: ({ id, data }: { id: number; data: any }) => platformApi.securityPoliciesUpdate(id, data),
```

### State / Modals
```tsx
isOpen={modalOpen}
isOpen={!!deleteTarget}
const [modalOpen, setModalOpen] = useState(false);
```

### File Statistics
- Lines: 184
- Size: 8913 chars

---

## SettingsPage

**File:** `eduadmin-dashboard/src/pages/SettingsPage.tsx`

### Imports
```tsx
import { useState, useEffect } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
import { settingsApi, schoolsApi } from '../api/client';
import { PageLoader } from '../components';
import { getChangedFields } from '../lib/diff';
import { hexToRgba } from '../lib/theme';
import type { SchoolTheme } from '../types';
import { toast } from '../components/Toast';
```

### Components Used
```tsx
if (isLoading) return <PageLoader />;
```

### API Calls
```
queryFn: () => schoolsApi.theme(),
mutationFn: settingsApi.updateSchool,
queryFn: settingsApi.list,
```

### File Statistics
- Lines: 455
- Size: 18398 chars

---

## SpecializationsPage

**File:** `eduadmin-dashboard/src/pages/SpecializationsPage.tsx`

### Imports
```tsx
import { useState, useMemo } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Plus, Pencil, Trash2, Award, Search } from 'lucide-react';
import { specializationsApi } from '../api/client';
import { type Specialization, getName } from '../types';
import { DataTable, ConfirmModal, EditModal, PageLoader } from '../components';
import { getChangedFields } from '../lib/diff';
import { toast } from '../components/Toast';
```

### Components Used
```tsx
<ConfirmModal
<EditModal
if (isLoading) return <PageLoader />;
<DataTable
```

### API Calls
```
mutationFn: specializationsApi.create,
mutationFn: (id: number) => specializationsApi.delete(id),
queryFn: specializationsApi.list,
specializationsApi.update(id, data),
```

### State / Modals
```tsx
isOpen={modalOpen}
isOpen={deleteModalOpen}
const [modalOpen, setModalOpen] = useState(false);
```

### Filters / Search State
```tsx
onChange={(e) => setSearchInput(e.target.value)}
return (specializations as Specialization[] | undefined)?.filter((s) => {
const filtered = useMemo(() => {
const [searchInput, setSearchInput] = useState('');
{filtered?.length ?? 0} تخصص
data={(filtered as any) || []}
```

### File Statistics
- Lines: 303
- Size: 10338 chars

---

## StudentDashboardPage

**File:** `eduadmin-dashboard/src/pages/StudentDashboardPage.tsx`

### Imports
```tsx
import { useQuery } from '@tanstack/react-query';
import {
import { programsApi, notificationsApi } from '../api/client';
import { type WeeklyProgram, getName } from '../types';
import { PageLoader } from '../components';
```

### Components Used
```tsx
if (scheduleLoading) return <PageLoader />;
```

### API Calls
```
queryFn: () => notificationsApi.myNotifications(),
queryFn: programsApi.mySchedule,
```

### Filters / Search State
```tsx
.filter((p) => p.day_of_week === todayDayIndex)
const unreadNotifs = ((notifications as any[] | undefined) || []).filter((n) => !n.is_read).length;
```

### File Statistics
- Lines: 267
- Size: 12444 chars

---

## StudentFormPage

**File:** `eduadmin-dashboard/src/pages/StudentFormPage.tsx`

### Imports
```tsx
import React, { useState, useEffect } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useNavigate, useParams, Link } from 'react-router-dom';
import {
import {
import { type LocalizedName, getName, type Evaluation, type EvaluationStats, type StudentInstallment, type Subject, type Parent, type RewardPenaltyRecord, type RewardPenaltyType } from '../types';
import { PageLoader } from '../components/LoadingSpinner';
import { SplitTabLayout } from '../components/SplitTabLayout';
import { EditModal } from '../components/EditModal';
import { buildWhatsAppUrl, buildStudentWhatsAppText } from '../lib/studentReport';
import { formatValidationDetails, lookupFieldErrors, toApiError } from '../lib/apiError';
import { ErrorSummary } from '../components/FormErrors';
import { toast } from '../components/Toast';
import { useAcademicYear } from '../context/AcademicYearContext';
import { useAuth } from '../context/AuthContext';
```

### Components Used
```tsx
<PageLoader />
<SplitTabLayout
<EditModal
```

### API Calls
```
queryFn: () => studentsApi.get(studentId),
mutationFn: (id: number) => rewardsPenaltiesApi.remove(id),
queryFn: () => subjectsApi.list(),
evaluationsApi.create({
queryFn: () => nationalitiesApi.list(),
queryFn: () => bloodTypesApi.list(),
queryFn: () => gradesApi.list(),
queryFn: () => parentsApi.list(),
queryFn: () => studentsApi.getSectionsByClassroom(parseInt(formData.Classroom_id, 10)),
queryFn: () => gendersApi.list(),
parentsApi.create({
mutationFn: (data: Record<string, unknown>) => studentsApi.update(sid, data),
rewardsPenaltiesApi.create({
queryFn: () => evaluationsApi.reportByStudent(studentId),
attendanceApi.bulkStore([
queryFn: () => paperExamsApi.list(),
return studentsApi.linkParent(studentId, Number(newId));
queryFn: () => paperExamsApi.getMarksByStudent(studentId),
paperExamsApi.saveMarks(Number(markForm.paper_exam_id), [
queryFn: () => teachersApi.list(),
studentsApi.linkParent(studentId, Number(parentForm.parent_id)).then(() => {
queryFn: () => studentsApi.getClassroomsByGrade(parseInt(formData.Grade_id, 10)),
queryFn: () => studentInstallmentsApi.list({ student_id: studentId }),
queryFn: () => rewardsPenaltiesApi.list({ student_id: studentId, per_page: 50 }),
queryFn: () => studentsApi.get(sid),
mutationFn: (data: Record<string, unknown>) => studentsApi.create(data),
```

### State / Modals
```tsx
isOpen={modal === 'mark'}
isOpen={modal === 'parent'}
isOpen={modal === 'evaluation'}
isOpen={modal === 'attendance'}
```

### Filters / Search State
```tsx
const studentSubjects: Subject[] = (allSubjects as Subject[]).filter(
const penalties = records.filter((r) => r.type === 'penalty');
const paidInstallments = installments.filter((i) => i.status === 'paid').length;
const examsForStudent = (Array.isArray(allExams) ? allExams : (allExams as any)?.data ?? []).filter(
const presentCount = attendance.filter((a) => {
const rewards = records.filter((r) => r.type === 'reward');
```

### Permissions
```tsx
const canMark = hasPermission('paper_exams.manage');
const canAttend = hasPermission('attendance.manage');
const canManage = hasPermission('rewards.manage');
const canParent = hasPermission('students.manage');
const canEvaluate = hasPermission('evaluations.manage');
const { hasPermission } = useAuth();
```

### File Statistics
- Lines: 2269
- Size: 92468 chars

---

## StudentInstallmentDetailPage

**File:** `eduadmin-dashboard/src/pages/StudentInstallmentDetailPage.tsx`

### Imports
```tsx
import { useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
import { studentInstallmentsApi, receiptsApi } from '../api/client';
import { DataTable, PageLoader } from '../components';
import { getName, type StudentInstallment, type StudentInstallmentReceipt } from '../types';
import { toast } from '../components/Toast';
```

### Components Used
```tsx
<DataTable
return <PageLoader />;
```

### API Calls
```
receiptsApi.create(data),
queryFn: () => studentInstallmentsApi.get(installmentId),
```

### File Statistics
- Lines: 275
- Size: 10416 chars

---

## StudentInstallmentsPage

**File:** `eduadmin-dashboard/src/pages/StudentInstallmentsPage.tsx`

### Imports
```tsx
import { useState } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { Eye, Filter, ArrowRight } from 'lucide-react';
import { studentInstallmentsApi, installmentPlansApi, studentsApi } from '../api/client';
import { DataTable, PageLoader } from '../components';
import { getName, type StudentInstallment } from '../types';
```

### Components Used
```tsx
<DataTable
return <PageLoader />;
```

### API Calls
```
const [planId, setPlanId] = useState(searchParams.get('plan_id') || '');
const [status, setStatus] = useState(searchParams.get('status') || '');
queryFn: () => installmentPlansApi.list(),
studentInstallmentsApi.list({
queryFn: () => studentsApi.list(),
const [studentId, setStudentId] = useState(searchParams.get('student_id') || '');
```

### Filters / Search State
```tsx
const [searchParams, setSearchParams] = useSearchParams();
setSearchParams(params);
```

### File Statistics
- Lines: 241
- Size: 8999 chars

---

## StudentReportDetailPage

**File:** `eduadmin-dashboard/src/pages/StudentReportDetailPage.tsx`

### Imports
```tsx
import { useParams, useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import {
import { studentReportsApi, studentsApi } from '../api/client';
import { getName, getProgressColor } from '../types';
import type { StudentMonthlyReport, ReportTrend, ReportTrendsData, EvaluationLevel } from '../types';
import { buildWhatsAppUrl, buildMonthlyReportWhatsAppText } from '../lib/studentReport';
import { downloadCsv } from '../lib/csv';
import {
```

### API Calls
```
queryFn: () => studentsApi.get(sid),
queryFn: () => studentReportsApi.trends(sid),
queryFn: () => studentReportsApi.detail(sid, mon, yr),
```

### File Statistics
- Lines: 420
- Size: 20285 chars

---

## StudentReportPage

**File:** `eduadmin-dashboard/src/pages/StudentReportPage.tsx`

### Imports
```tsx
import React, { useMemo } from 'react';
import { useQuery } from '@tanstack/react-query';
import { useParams, Link } from 'react-router-dom';
import { ArrowRight, MessageCircle, Printer, Mail, Phone, FileDown } from 'lucide-react';
import {
import {
import { PageLoader } from '../components/LoadingSpinner';
import { downloadCsv } from '../lib/csv';
import {
```

### Components Used
```tsx
<PageLoader />
```

### API Calls
```
queryFn: () => nationalitiesApi.list(),
queryFn: () => bloodTypesApi.list(),
queryFn: () => paperExamsApi.getMarksByStudent(studentId),
queryFn: () => studentsApi.get(studentId),
queryFn: () => subjectsApi.list(),
queryFn: () => gendersApi.list(),
queryFn: () => evaluationsApi.reportByStudent(studentId),
queryFn: () => studentInstallmentsApi.list({ student_id: studentId }),
```

### Filters / Search State
```tsx
const paidInstallments = installments.filter((i) => i.status === 'paid').length;
const presentCount = (attendance || []).filter((a) => {
```

### File Statistics
- Lines: 569
- Size: 22916 chars

---

## StudentReportsPage

**File:** `eduadmin-dashboard/src/pages/StudentReportsPage.tsx`

### Imports
```tsx
import { useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import {
import { studentReportsApi, studentsApi, gradesApi } from '../api/client';
import { getName, getProgressColor } from '../types';
import type {
import { PageLoader } from '../components';
```

### Components Used
```tsx
<PageLoader />
```

### API Calls
```
mutationFn: (payload: GenerateReportPayload) => studentReportsApi.generate(payload),
const { data: grades } = useQuery({ queryKey: ['grades'], queryFn: () => gradesApi.list() });
const res = await studentReportsApi.exportCsv({
const { data: students } = useQuery({ queryKey: ['students', 'all'], queryFn: () => studentsApi.list() });
queryFn: () => studentReportsApi.list({
```

### Filters / Search State
```tsx
) : filteredReports.length === 0 ? (
const filteredReports = useMemo(() => {
{filteredReports.map((report: StudentMonthlyReport) => {
.filter((p) => Math.abs(p - page) <= 2 || p === 1 || p === data.pagination!.last_page)
onChange={(e) => setSearch(e.target.value)}
const [search, setSearch] = useState('');
.filter((r) => r.student?.name?.toLowerCase().includes(q))
```

### File Statistics
- Lines: 520
- Size: 25507 chars

---

## StudentTranscriptsPage

**File:** `eduadmin-dashboard/src/pages/StudentTranscriptsPage.tsx`

### Imports
```tsx
import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import {
import { studentsApi, transcriptsApi, academicYearsApi, gradesApi } from '../api/client';
import { useAcademicYear } from '../context/AcademicYearContext';
import { DataTable } from '../components';
import { toast } from '../components/Toast';
import { getName, type AcademicTerm, type Student } from '../types';
```

### Components Used
```tsx
<DataTable
```

### API Calls
```
? await transcriptsApi.getYearly(studentId)
queryFn: () => gradesApi.list(),
queryFn: () => studentsApi.getClassroomsByGrade(Number(filterGradeId)),
: await transcriptsApi.get(studentId, termId === '' ? null : Number(termId));
(a, b) => (rank.get(a.subject_id) ?? 999) - (rank.get(b.subject_id) ?? 999)
studentsApi.list({
queryFn: () => academicYearsApi.terms(selectedYearId!),
queryFn: () => studentsApi.getSectionsByClassroom(Number(filterClassroomId)),
```

### Filters / Search State
```tsx
const [filterClassroomId, setFilterClassroomId] = useState<number | ''>('');
queryFn: () => studentsApi.getClassroomsByGrade(Number(filterGradeId)),
setSelectedIds((prev) => (prev.includes(nid) ? prev.filter((x) => x !== nid) : [...prev, nid]));
value={filterSectionId}
enabled: filterClassroomId !== '',
// Students of the selected academic year + stage/classroom/section filters
const [filterSectionId, setFilterSectionId] = useState<number | ''>('');
value={filterGradeId}
queryKey: ['grade-classrooms', filterGradeId],
queryFn: () => studentsApi.getSectionsByClassroom(Number(filterClassroomId)),
section_id: filterSectionId === '' ? undefined : Number(filterSectionId),
disabled={filterGradeId === ''}
classroom_id: filterClassroomId === '' ? undefined : Number(filterClassroomId),
results.filter((r) => r.transcript).map((r) => r.transcript!)
enabled: filterGradeId !== '',
{/* Students selection table — only this section reloads when filtering */}
const okCount = generated.filter((g) => g.transcript).length;
Grade_id: filterGradeId === '' ? undefined : Number(filterGradeId),
{/* Stage / classroom / section filters */}
// Keep showing the previous rows while the new filtered set loads
queryKey: ['students', 'transcripts', selectedYearId, filterGradeId, filterClassroomId, filterSectionId],
queryKey: ['classroom-sections', filterClassroomId],
const [filterGradeId, setFilterGradeId] = useState<number | ''>('');
value={filterClassroomId}
disabled={filterClassroomId === ''}
```

### File Statistics
- Lines: 884
- Size: 40535 chars

---

## StudentsPage

**File:** `eduadmin-dashboard/src/pages/StudentsPage.tsx`

### Imports
```tsx
import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useNavigate } from 'react-router-dom';
import { Plus, Pencil, Trash2, GraduationCap, Search, FilterX } from 'lucide-react';
import { studentsApi, gradesApi } from '../api/client';
import { type Student, type Grade, getName } from '../types';
import { DataTable } from '../components/DataTable';
import { ConfirmModal } from '../components/ConfirmModal';
import { useDebouncedValue } from '../hooks/useDebouncedValue';
import { toast } from '../components/Toast';
import { useAcademicYear } from '../context/AcademicYearContext';
```

### Components Used
```tsx
<ConfirmModal
<DataTable
```

### API Calls
```
mutationFn: (id: number) => studentsApi.delete(id),
queryFn: () => gradesApi.list(),
queryFn: () => studentsApi.getClassroomsByGrade(Number(filterGradeId)),
studentsApi.list({
queryFn: () => studentsApi.getSectionsByClassroom(Number(filterClassroomId)),
```

### State / Modals
```tsx
isOpen={deleteModalOpen}
```

### Filters / Search State
```tsx
const [filterClassroomId, setFilterClassroomId] = useState<number | ''>('');
queryFn: () => studentsApi.getClassroomsByGrade(Number(filterGradeId)),
value={filterSectionId}
enabled: filterClassroomId !== '',
{(filterGradeId !== '' || filterClassroomId !== '' || filterSectionId !== '') && (
const [filterSectionId, setFilterSectionId] = useState<number | ''>('');
value={filterGradeId}
queryKey: ['grade-classrooms', filterGradeId],
queryKey: ['students', searchQuery, filterGradeId, filterClassroomId, filterSectionId, selectedYearId],
queryFn: () => studentsApi.getSectionsByClassroom(Number(filterClassroomId)),
section_id: filterSectionId === '' ? undefined : Number(filterSectionId),
const [searchInput, setSearchInput] = useState('');
classroom_id: filterClassroomId === '' ? undefined : Number(filterClassroomId),
enabled: filterGradeId !== '',
Grade_id: filterGradeId === '' ? undefined : Number(filterGradeId),
setSearchInput(e.target.value);
queryKey: ['classroom-sections', filterClassroomId],
const [filterGradeId, setFilterGradeId] = useState<number | ''>('');
value={filterClassroomId}
```

### File Statistics
- Lines: 387
- Size: 14188 chars

---

## SubjectGradeSettingsPage

**File:** `eduadmin-dashboard/src/pages/SubjectGradeSettingsPage.tsx`

### Imports
```tsx
import { useState, useMemo } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { SlidersHorizontal, Save, Trash2, CheckCircle2, CircleDashed } from 'lucide-react';
import { subjectGradeSettingsApi, subjectsApi, gradesApi, classroomsApi } from '../api/client';
import { getName } from '../types';
import { ConfirmModal, PageLoader } from '../components';
import { toast } from '../components/Toast';
```

### Components Used
```tsx
if (isLoading && ready) return <PageLoader />;
<ConfirmModal
```

### API Calls
```
const { data: classrooms } = useQuery({ queryKey: ['classrooms'], queryFn: () => classroomsApi.list() });
const { data: grades } = useQuery({ queryKey: ['grades'], queryFn: () => gradesApi.list() });
const { data: subjects } = useQuery({ queryKey: ['subjects'], queryFn: () => subjectsApi.list() });
subjectGradeSettingsApi.save(data),
mutationFn: (id: number) => subjectGradeSettingsApi.delete(id),
queryFn: () => subjectGradeSettingsApi.list(classroomId ? { classroom_id: classroomId } : undefined),
Promise.all(items.map((item) => subjectGradeSettingsApi.save(item))),
```

### State / Modals
```tsx
isOpen={deleteId !== null}
```

### Filters / Search State
```tsx
const selectedClassroom = gradeClassrooms.find((c: any) => c.id === classroomId);
return classrooms.filter((c: any) => c.Grade_id === gradeId || c.grade_id === gradeId);
مواد الصف: <span className="text-blue-600">{getName(selectedClassroom?.Name_Class)}</span>
return subjects.filter((s: any) => s.grade_id === gradeId || s.Grade_id === gradeId);
```

### File Statistics
- Lines: 300
- Size: 14130 chars

---

## SubjectsPage

**File:** `eduadmin-dashboard/src/pages/SubjectsPage.tsx`

### Imports
```tsx
import { useState, useEffect } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Plus, Pencil, Trash2, BookOpen, X, Search } from 'lucide-react';
import { subjectsApi, gradesApi, classroomsApi, teachersApi } from '../api/client';
import { useClassroomsByGrade } from '../hooks/useDependentOptions';
import { getName } from '../types';
import { EditModal, ConfirmModal } from '../components';
import { getChangedFields } from '../lib/diff';
import { toast } from '../components/Toast';
```

### Components Used
```tsx
<EditModal
<ConfirmModal
```

### API Calls
```
mutationFn: (data: SubjectFormData) => subjectsApi.create(data),
mutationFn: (id: number) => subjectsApi.delete(id),
queryFn: () => gradesApi.list(),
queryFn: () => subjectsApi.list(),
queryFn: () => classroomsApi.list(),
queryFn: () => teachersApi.list(),
subjectsApi.update(id, data),
```

### State / Modals
```tsx
isOpen={editModalOpen}
isOpen={showForm}
isOpen={deleteId !== null}
```

### Filters / Search State
```tsx
{filteredSubjects?.length === 0 && (
value={searchTerm}
const term = searchTerm.trim().toLowerCase();
return (subjects as Subject[] | undefined)?.filter((s) => {
<p>{searchTerm ? 'لا توجد نتائج مطابقة للبحث' : 'لا توجد مواد دراسية مسجلة'}</p>
{filteredSubjects?.map((subject: Subject, index: number) => (
onChange={(e) => setSearchTerm(e.target.value)}
<span className="text-sm text-gray-500">{filteredSubjects?.length ?? 0} مادة</span>
const [searchTerm, setSearchTerm] = useState('');
const filteredSubjects = (() => {
```

### File Statistics
- Lines: 542
- Size: 22201 chars

---

## SubscriptionsPage

**File:** `eduadmin-dashboard/src/pages/SubscriptionsPage.tsx`

### Imports
```tsx
import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Plus, BadgeDollarSign, Pencil, Trash2 } from 'lucide-react';
import { platformApi, schoolsApi } from '../api/client';
import { type Subscription, type Plan, type SchoolRecord, getName } from '../types';
import { DataTable, EditModal, PageLoader, ConfirmModal } from '../components';
import { toast } from '../components/Toast';
```

### Components Used
```tsx
<ConfirmModal
<EditModal
if (isLoading) return <PageLoader />;
<DataTable
```

### API Calls
```
const { data: plans } = useQuery({ queryKey: ['platform-plans'], queryFn: platformApi.plansList, placeholderData: (prev) => prev as any });
mutationFn: ({ id, data }: { id: number; data: any }) => platformApi.subscriptionsUpdate(id, data),
mutationFn: (id: number) => platformApi.subscriptionsDelete(id),
const { data: schools } = useQuery({ queryKey: ['schools'], queryFn: schoolsApi.list, placeholderData: (prev) => prev as any });
mutationFn: platformApi.subscriptionsCreate,
queryFn: () => platformApi.subscriptionsList({ per_page: 100 }),
```

### State / Modals
```tsx
isOpen={modalOpen}
isOpen={!!deleteTarget}
const [modalOpen, setModalOpen] = useState(false);
```

### File Statistics
- Lines: 206
- Size: 10418 chars

---

## TeacherAvailabilityPage

**File:** `eduadmin-dashboard/src/pages/TeacherAvailabilityPage.tsx`

### Imports
```tsx
import { useState, useRef, useEffect } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Clock, Save, Star, User, ChevronDown } from 'lucide-react';
import { teachersApi, teacherAvailabilityApi } from '../api/client';
import { type Teacher, type TeacherWeeklySchedule, type TeacherPreference, getName } from '../types';
import { PageLoader } from '../components';
import { toast } from '../components/Toast';
```

### Components Used
```tsx
<PageLoader />
```

### API Calls
```
queryFn: () => (teacherId ? teacherAvailabilityApi.preferences({ teacher_id: teacherId }) : Promise.resolve([])),
teacherAvailabilityApi.savePreferences({
queryFn: () => (teacherId ? teacherAvailabilityApi.list({ teacher_id: teacherId }) : Promise.resolve([])),
queryFn: () => teachersApi.list() as Promise<Teacher[]>,
await teacherAvailabilityApi.bulkStore(teacherId, toUpsert);
await Promise.all(toRemove.map((id) => teacherAvailabilityApi.remove(id)));
```

### Filters / Search State
```tsx
: pref.avoid_days.filter((x) => x !== day),
.filter((d) => !d.is_available && rowIds[d.day])
.filter((d) => d.is_available)
```

### File Statistics
- Lines: 507
- Size: 19440 chars

---

## TeacherDashboardPage

**File:** `eduadmin-dashboard/src/pages/TeacherDashboardPage.tsx`

### Imports
```tsx
import { useQuery } from '@tanstack/react-query';
import {
import { teacherDashboardApi } from '../api/client';
import { type TeacherDashboardData } from '../types';
import { PageLoader } from '../components';
```

### Components Used
```tsx
if (isLoading) return <PageLoader />;
```

### API Calls
```
queryFn: teacherDashboardApi.show,
```

### File Statistics
- Lines: 391
- Size: 20290 chars

---

## TeacherDetailsPage

**File:** `eduadmin-dashboard/src/pages/TeacherDetailsPage.tsx`

### Imports
```tsx
import { useMemo, useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { useNavigate, useParams } from 'react-router-dom';
import {
import { teachersApi, salariesApi } from '../api/client';
import {
import { PageLoader, SplitTabLayout } from '../components';
import { formatDate } from '../lib/utils';
```

### Components Used
```tsx
<SplitTabLayout
return <PageLoader message="جاري تحميل بيانات المعلم..." />;
```

### API Calls
```
const entry = grouped.get(name) ?? {
queryFn: () => salariesApi.list({ employee_type: 'teacher', employee_id: teacherId }),
queryFn: () => teachersApi.get(teacherId),
```

### Filters / Search State
```tsx
const paid = sorted.filter((row) => row.status === 'paid');
.filter((payment) => payment.notes)
const due = sorted.filter((row) => row.status === 'pending');
```

### File Statistics
- Lines: 626
- Size: 25365 chars

---

## TeacherFormPage

**File:** `eduadmin-dashboard/src/pages/TeacherFormPage.tsx`

### Imports
```tsx
import { useState, useEffect } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useNavigate, useParams } from 'react-router-dom';
import { Save, X, Loader2, UserCog, User, Briefcase } from 'lucide-react';
import { teachersApi, specializationsApi, gendersApi, isDeferredAction } from '../api/client';
import type { Teacher } from '../types';
import { getName, type LocalizedName } from '../types';
import { PageLoader, SplitTabLayout } from '../components';
import { toast } from '../components/Toast';
```

### Components Used
```tsx
<SplitTabLayout
return <PageLoader message={isEditMode ? 'جاري تحميل بيانات المعلم...' : 'جاري التحميل...'} />;
```

### API Calls
```
queryFn: specializationsApi.list,
mutationFn: (data: Record<string, unknown>) => teachersApi.create(data),
queryFn: gendersApi.list,
teachersApi.update(id, data),
queryFn: () => teachersApi.get(teacherId!),
```

### Filters / Search State
```tsx
setInstallments((prev) => prev.filter((_, i) => i !== index))
.filter((i) => i.amount)
.filter((i) => i.amount && i.due_date)
const valid = installments.filter((i) => i.amount && i.due_date);
```

### File Statistics
- Lines: 879
- Size: 33632 chars

---

## TeacherSalaryHistoryPage

**File:** `eduadmin-dashboard/src/pages/TeacherSalaryHistoryPage.tsx`

### Imports
```tsx
import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useNavigate, useParams } from 'react-router-dom';
import { ArrowRight, Wallet, CheckCircle2, Clock, Banknote, Save, Loader2 } from 'lucide-react';
import { teachersApi, salariesApi } from '../api/client';
import { DataTable, PageLoader } from '../components';
import { getName, type Teacher, type SalaryPayment } from '../types';
import { toast } from '../components/Toast';
```

### Components Used
```tsx
<DataTable
return <PageLoader message="جاري تحميل سجل الرواتب..." />;
```

### API Calls
```
queryFn: () => salariesApi.list({ employee_type: 'teacher', employee_id: teacherId }),
queryFn: () => teachersApi.get(teacherId),
salariesApi.pay(salaryId, data),
salariesApi.updateBaseSalary('teacher', teacherId, salary),
```

### Filters / Search State
```tsx
const paidCount = payments.filter((p) => p.status === 'paid').length;
const pendingCount = payments.filter((p) => p.status === 'pending').length;
.filter((p) => p.status === 'paid')
```

### File Statistics
- Lines: 384
- Size: 15761 chars

---

## TeacherWorkHoursPage

**File:** `eduadmin-dashboard/src/pages/TeacherWorkHoursPage.tsx`

### Imports
```tsx
import { useMemo, useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Clock, Trash2, Timer, CalendarDays, Wallet } from 'lucide-react';
import { teachersApi, teacherWorkHoursApi } from '../api/client';
import { DataTable, ConfirmModal, PageLoader } from '../components';
import { getName, type Teacher, type TeacherWorkHourEntry } from '../types';
import { toast } from '../components/Toast';
```

### Components Used
```tsx
if (loadingTeachers) return <PageLoader message="جاري التحميل..." />;
<ConfirmModal
<DataTable
```

### API Calls
```
teacherWorkHoursApi.create(data),
teacherWorkHoursApi.list({
queryFn: () => teachersApi.list() as Promise<Teacher[]>,
mutationFn: (id: number) => teacherWorkHoursApi.delete(id),
```

### State / Modals
```tsx
isOpen={!!deleteTarget}
```

### Filters / Search State
```tsx
const [filterMonth, setFilterMonth] = useState(today().slice(0, 7)); // YYYY-MM
const from = filterMonth ? `${filterMonth}-01` : undefined;
const to = filterMonth
select: (list) => (list as Teacher[]).filter((t) => (t.salary_type ?? 'monthly') === 'hourly'),
value={filterMonth}
{/* Month summary + filter */}
queryKey: ['teacher-work-hours', teacherId, filterMonth],
? `${filterMonth}-${new Date(Number(filterMonth.slice(0, 4)), Number(filterMonth.slice(5, 7)), 0).getDate()}`
```

### File Statistics
- Lines: 364
- Size: 14903 chars

---

## TeachersPage

**File:** `eduadmin-dashboard/src/pages/TeachersPage.tsx`

### Imports
```tsx
import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useNavigate } from 'react-router-dom';
import { Plus, Pencil, Trash2, UserCog, Wallet, Search, Eye } from 'lucide-react';
import { teachersApi } from '../api/client';
import { type Teacher, getName, getGenderName } from '../types';
import { DataTable, ConfirmModal, PageLoader } from '../components';
import { toast } from '../components/Toast';
```

### Components Used
```tsx
<DataTable
<ConfirmModal
return <PageLoader />;
```

### API Calls
```
queryFn: teachersApi.list,
mutationFn: (id: number) => teachersApi.delete(id),
```

### State / Modals
```tsx
isOpen={deleteModalOpen}
```

### Filters / Search State
```tsx
searchTerm
value={searchTerm}
const term = searchTerm.trim();
data={filteredTeachers}
<span className="text-sm text-gray-500">{filteredTeachers.length} معلم</span>
onChange={(e) => setSearchTerm(e.target.value)}
const [searchTerm, setSearchTerm] = useState('');
const filteredTeachers = teachers.filter((teacher: Teacher) => {
```

### File Statistics
- Lines: 251
- Size: 8766 chars

---

## TimetableScenariosPage

**File:** `eduadmin-dashboard/src/pages/TimetableScenariosPage.tsx`

### Imports
```tsx
import { useEffect, useMemo, useRef, useState, type DragEvent } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { GitBranch, Plus, CheckCircle, Archive, Copy, Trash2, BarChart3, Settings, Zap, RefreshCw, Eye, Pencil, Clock, CalendarDays, Save, RotateCcw, Coffee } from 'lucide-react';
import { scenariosApi, gradesApi, classroomsApi, sectionsApi, subjectRequirementsApi, subjectsApi, programsApi, teachersApi, academicYearsApi, schedulePeriodsApi } from '../api/client';
import { type Grade, type Classroom, type Section, type TimetableScenario, type SubjectWeeklyRequirement, type WeeklyProgram, type SchedulePeriod, getName } from '../types';
import { EditModal, ConfirmModal } from '../components';
import { toast } from '../components/Toast';
```

### Components Used
```tsx
<EditModal
<ConfirmModal
```

### API Calls
```
await programsApi.remove(id);
await programsApi.update(Number(idStr), data);
mutationFn: (id: number) => scenariosApi.recalculate(id, {}),
scenariosApi.list({
queryFn: () => scenariosApi.get(previewId as number),
mutationFn: (id: number) => subjectRequirementsApi.remove(id),
const { data: teachers } = useQuery({ queryKey: ['teachers'], queryFn: teachersApi.list });
queryFn: () => schedulePeriodsApi.byYear(activeYearId as number),
mutationFn: (data: any) => scenariosApi.create(data),
const { data: sections, isLoading: sectionsLoading } = useQuery({ queryKey: ['sections'], queryFn: sectionsApi.list });
mutationFn: (id: number) => scenariosApi.duplicate(id),
subjectRequirementsApi.list({
scenariosApi.optimize(data),
mutationFn: (id: number) => scenariosApi.remove(id),
queryFn: () => scenariosApi.compare(compareIds),
const created = await programsApi.create({
mutationFn: (id: number) => scenariosApi.unpublish(id),
const { data: classrooms, isLoading: classroomsLoading } = useQuery({ queryKey: ['classrooms'], queryFn: classroomsApi.list });
const res = await scenariosApi.alternatives({
const { data: grades, isLoading: gradesLoading } = useQuery({ queryKey: ['grades'], queryFn: gradesApi.list });
mutationFn: (data: any) => subjectRequirementsApi.create(data),
mutationFn: (id: number) => scenariosApi.publish(id),
queryFn: () => academicYearsApi.list(),
const { data: subjects } = useQuery({ queryKey: ['subjects'], queryFn: subjectsApi.list });
```

### State / Modals
```tsx
isOpen={addSlot !== null}
isOpen={altOpen}
isOpen={deleteId !== null}
isOpen={reqOpen}
isOpen={previewId !== null}
isOpen={createOpen}
isOpen={editProgram !== null}
isOpen={deleteProgramId !== null}
```

### Filters / Search State
```tsx
const globals = all.filter((p) => p.day_of_week === null);
const filteredSections = useMemo(() => {
setDraftPrograms((prev) => (prev ?? []).filter((p) => p.id !== id));
const dayRows = all.filter((p) => p.day_of_week === day);
.filter((s) => !sectionId || String(s.section_id) === sectionId)
return Array.from(set).filter((t) => t.length === 5).sort();
result = result.filter((s) => s.Class_id === Number(classroomId));
setPendingCreates((prev) => prev.filter((p) => p.id !== id));
return gradeId ? classroomList.filter((c) => c.Grade_id === Number(gradeId)) : classroomList;
return base.filter((p) => !pendingDeletes.includes(p.id));
const filteredClassrooms = useMemo(() => {
{filteredClassrooms.map((c) => (
prev.includes(id) ? prev.filter((i) => i !== id) : prev.length < 5 ? [...prev, id] : prev
{filteredSections.map((s) => (
const cellPrograms = previewPrograms.filter(
return reqGradeId ? classroomList.filter((c) => c.Grade_id === Number(reqGradeId)) : classroomList;
let result = gradeId ? list.filter((s) => s.Grade_id === Number(gradeId)) : list;
```

### File Statistics
- Lines: 1678
- Size: 76579 chars

---

## TranscriptPreviewPage

**File:** `eduadmin-dashboard/src/pages/TranscriptPreviewPage.tsx`

### Imports
```tsx
import { useMemo, useRef } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import { ArrowRight, Printer } from 'lucide-react';
import {
```

### File Statistics
- Lines: 106
- Size: 3783 chars

---

## WeeklyProgramsPage

**File:** `eduadmin-dashboard/src/pages/WeeklyProgramsPage.tsx`

### Imports
```tsx
import { useMemo, useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { CalendarDays, Plus, Pencil, Trash2, Send, Clock, Zap, CheckCircle2, AlertTriangle, ListChecks, Coffee, RotateCcw } from 'lucide-react';
import {
import {
import { EditModal, ConfirmModal, PageLoader } from '../components';
import { toast } from '../components/Toast';
```

### Components Used
```tsx
<EditModal
if (isLoading) return <PageLoader />;
<ConfirmModal
```

### API Calls
```
queryFn: sectionsApi.list,
queryFn: teachersApi.list,
programsApi.notify(data),
mutationFn: (id: number) => programsApi.remove(id),
mutationFn: (ids: number[]) => programsApi.removeMany(ids),
queryFn: () => schedulePeriodsApi.byYear(activeYearId as number),
mutationFn: (sectionId: number) => programsApi.reset({ section_id: sectionId }),
queryFn: () => programsApi.list({ section_id: selectedSectionId ?? undefined }),
queryFn: subjectsApi.list,
scenariosApi.validate(data),
queryFn: gradesApi.list,
scenariosApi.optimize(data),
mutationFn: (data: any) => programsApi.create(data),
mutationFn: ({ id, data }: { id: number; data: any }) => programsApi.update(id, data),
queryFn: classroomsApi.list,
queryFn: () => academicYearsApi.list(),
```

### State / Modals
```tsx
isOpen={notifyOpen}
isOpen={bulkDeleteConfirm}
isOpen={formOpen}
isOpen={deleteId !== null}
```

### Filters / Search State
```tsx
{/* Cascade filters */}
const globals = all.filter((p) => p.day_of_week === null);
const selectedSection = filteredSections.find((s) => s.id === Number(sectionId)) || null;
return all.filter(
const dayRows = all.filter((p) => p.day_of_week === day);
() => (classId ? sectionList.filter((s) => s.Class_id === Number(classId)) : sectionList),
].filter(Boolean) as string[];
((programs as WeeklyProgram[] | undefined) || []).filter(
() => (gradeId ? classroomList.filter((c) => c.Grade_id === Number(gradeId)) : classroomList),
{filteredClassrooms.map((c) => (
const filteredClassrooms = useMemo(
? sectionList.filter((s) => s.Class_id === Number(classId)).map((s) => s.id)
{filteredSections.map((s) => (
.filter((o) => o.available)
? sectionList.filter((s) => s.Grade_id === Number(gradeId)).map((s) => s.id)
prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id]
const filteredSections = useMemo(
```

### File Statistics
- Lines: 1127
- Size: 48068 chars

---
