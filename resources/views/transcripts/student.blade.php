<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>الجلاء المدرسي - {{ $transcript['student']['name'] }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
            background: #f0f2f5;
            color: #1e2942;
            padding: 20px;
        }
        .transcript {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            border: 2px solid #1e2942;
            border-radius: 8px;
            padding: 30px 40px;
        }
        .header { display: flex; align-items: center; justify-content: space-between; border-bottom: 3px double #1e2942; padding-bottom: 15px; }
        .header .logo { width: 90px; height: 90px; object-fit: contain; }
        .header .school-info { text-align: center; flex: 1; }
        .header .school-info h1 { font-size: 22px; margin-bottom: 5px; }
        .header .school-info p { font-size: 13px; color: #555; }
        .title { text-align: center; margin: 20px 0; }
        .title h2 { display: inline-block; background: #1e2942; color: #fff; padding: 8px 40px; border-radius: 6px; font-size: 20px; }
        .student-info { display: grid; grid-template-columns: 1fr 1fr; gap: 8px 30px; margin-bottom: 20px; font-size: 15px; }
        .student-info .item { border-bottom: 1px dotted #aaa; padding: 4px 0; }
        .student-info .item b { color: #1e2942; }
        table.marks { width: 100%; border-collapse: collapse; margin: 15px 0; }
        table.marks th, table.marks td { border: 1px solid #1e2942; padding: 8px; text-align: center; font-size: 14px; }
        table.marks th { background: #1e2942; color: #fff; }
        table.marks td.final { color: #0000cc; font-weight: bold; }
        table.marks td.failed { color: #cc0000; font-weight: bold; }
        .summary { margin-top: 20px; font-size: 15px; }
        .summary .row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #ddd; }
        .summary .avg { color: #cc0000; font-weight: bold; }
        .result { text-align: center; margin: 25px 0; font-size: 22px; font-weight: bold; }
        .result.pass { color: #006600; }
        .result.fail { color: #cc0000; }
        .signatures { display: flex; justify-content: space-between; margin-top: 60px; font-size: 14px; }
        .signatures div { text-align: center; width: 30%; border-top: 1px solid #000; padding-top: 8px; }
        .actions { text-align: center; margin: 20px auto; max-width: 800px; }
        .actions button { background: #0088cc; color: #fff; border: none; padding: 10px 40px; font-size: 16px; border-radius: 6px; cursor: pointer; }
        .actions button:hover { background: #1e2942; }
        @media print {
            body { background: #fff; padding: 0; }
            .actions { display: none; }
            .transcript { border-radius: 0; max-width: 100%; }
        }
    </style>
</head>
<body>

<div class="actions">
    <button onclick="window.print()">🖨️ طباعة الجلاء</button>
</div>

<div class="transcript">
    <div class="header">
        @if($school && $school->logoUrl())
            <img src="{{ $school->logoUrl() }}" alt="شعار المدرسة" class="logo">
        @endif
        <div class="school-info">
            <h1>{{ $school->name ?? 'اسم المدرسة' }}</h1>
            @if($school)
                <p>{{ $school->address }}</p>
                <p>{{ $school->phone }} - {{ $school->email }}</p>
            @endif
        </div>
        @if($school && $school->logoUrl())
            <img src="{{ $school->logoUrl() }}" alt="شعار المدرسة" class="logo">
        @endif
    </div>

    <div class="title"><h2>الجلاء المدرسي</h2></div>

    <div class="student-info">
        <div class="item"><b>الاسم والنسبة:</b> {{ is_array($transcript['student']['name']) ? ($transcript['student']['name']['ar'] ?? reset($transcript['student']['name'])) : $transcript['student']['name'] }}</div>
        <div class="item"><b>النسبة (الاسم الثاني):</b> {{ $transcript['student']['second_name'] ?? '—' }}</div>
        <div class="item"><b>الصف:</b> {{ $transcript['student']['grade'] }}</div>
        <div class="item"><b>الشعبة:</b> {{ $transcript['student']['section'] }}</div>
        <div class="item"><b>الفصل:</b> {{ $transcript['student']['term'] }}</div>
        <div class="item"><b>العام الدراسي:</b> {{ $transcript['student']['academic_year'] }}</div>
        <div class="item"><b>الرقم الامتحاني:</b> {{ $transcript['student']['id'] }}</div>
    </div>

    <table class="marks">
        <thead>
        <tr>
            <th>المادة</th>
            @if($transcript['settings']['calculation_mode'] === 'coursework_exam')
                <th>درجة الأعمال ({{ rtrim(rtrim(number_format($transcript['settings']['coursework_max'], 2), '0'), '.') }})</th>
                <th>درجة الامتحان ({{ rtrim(rtrim(number_format($transcript['settings']['exam_max'], 2), '0'), '.') }})</th>
            @endif
            <th>المحصلة</th>
            <th>التقدير</th>
            <th>النتيجة</th>
        </tr>
        </thead>
        <tbody>
        @foreach($transcript['subjects'] as $subject)
            <tr>
                <td>{{ is_array($subject['subject_name']) ? ($subject['subject_name']['ar'] ?? reset($subject['subject_name'])) : $subject['subject_name'] }}</td>
                @if($transcript['settings']['calculation_mode'] === 'coursework_exam')
                    <td>{{ rtrim(rtrim(number_format($subject['coursework'], 2), '0'), '.') }}</td>
                    <td>{{ rtrim(rtrim(number_format($subject['exam'], 2), '0'), '.') }}</td>
                @endif
                <td class="final {{ $subject['passed'] ? '' : 'failed' }}">{{ rtrim(rtrim(number_format($subject['final'], 2), '0'), '.') }}</td>
                <td>{{ $subject['grade_level'] }}</td>
                <td class="{{ $subject['passed'] ? '' : 'failed' }}">{{ $subject['passed'] ? 'ناجح' : 'راسب' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="summary">
        <div class="row">
            <span><b>المتوسط العام:</b> <span class="avg">{{ rtrim(rtrim(number_format($transcript['average'], 2), '0'), '.') }}%</span></span>
            <span><b>التقدير العام:</b> <span class="avg">{{ $transcript['average_grade_level'] }}</span></span>
        </div>
    </div>

    <div class="result {{ $transcript['passed'] ? 'pass' : 'fail' }}">
        @if($transcript['passed'])
            ناجح
        @else
            راسب
            @if(count($transcript['failed_subjects']))
                <div style="font-size:15px; margin-top:8px; color:#cc0000;">
                    ({{ implode('، ', array_map(fn ($s) => is_array($s['subject_name']) ? ($s['subject_name']['ar'] ?? reset($s['subject_name'])) : $s['subject_name'], $transcript['failed_subjects'])) }})
                </div>
            @endif
        @endif
    </div>

    <div class="signatures">
        <div>مدير المدرسة</div>
        <div>معلم الصف</div>
        <div>ولي الأمر</div>
    </div>
</div>

</body>
</html>
