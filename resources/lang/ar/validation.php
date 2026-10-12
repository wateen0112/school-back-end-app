<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | رسائل التحقق بالعربية. يُستبدل :attribute باسم الحقل، لذا تبدأ الرسائل بـ
    | "حقل :attribute" بدلاً من "The :attribute" لتفادي خلط اللغتين.
    |
    */

    'accepted' => 'يجب قبول :attribute.',
    'active_url' => ':attribute ليس رابطاً صحيحاً.',
    'after' => 'يجب أن يكون تاريخ :attribute بعد تاريخ :date.',
    'after_or_equal' => 'يجب أن يكون تاريخ :attribute بعد أو يساوي تاريخ :date.',
    'alpha' => 'يجب أن يحتوي :attribute على حروف فقط.',
    'alpha_dash' => 'يجب أن يحتوي :attribute على حروف وأرقام وشرطات وشرطات سفلية فقط.',
    'alpha_num' => 'يجب أن يحتوي :attribute على حروف وأرقام فقط.',
    'array' => 'يجب أن يكون :attribute مصفوفة.',
    'before' => 'يجب أن يكون تاريخ :attribute قبل تاريخ :date.',
    'before_or_equal' => 'يجب أن يكون تاريخ :attribute قبل أو يساوي تاريخ :date.',
    'between' => [
        'numeric' => 'يجب أن تكون قيمة :attribute بين :min و :max.',
        'file' => 'يجب أن يكون حجم :attribute بين :min و :max كيلوبايت.',
        'string' => 'يجب أن يكون طول :attribute بين :min و :max حرفاً.',
        'array' => 'يجب أن يحتوي :attribute على عدد عناصر بين :min و :max.',
    ],
    'boolean' => 'يجب أن تكون قيمة :attribute صحيحة أو خاطئة.',
    'confirmed' => 'تأكيد :attribute غير مطابق.',
    'current_password' => 'كلمة المرور الحالية غير صحيحة.',
    'date' => ':attribute ليس تاريخاً صحيحاً.',
    'date_equals' => 'يجب أن يكون تاريخ :attribute مساوياً لتاريخ :date.',
    'date_format' => ':attribute لا يطابق الصيغة :format.',
    'different' => 'يجب أن يكون :attribute و :other مختلفين.',
    'digits' => 'يجب أن يتكون :attribute من :digits أرقام.',
    'digits_between' => 'يجب أن يتكون :attribute من عدد أرقام بين :min و :max.',
    'dimensions' => 'أبعاد صورة :attribute غير صالحة.',
    'distinct' => 'قيمة :attribute مكررة.',
    'email' => 'يجب أن يكون :attribute بريداً إلكترونياً صحيحاً.',
    'ends_with' => 'يجب أن ينتهي :attribute بأحد التالي: :values.',
    'exists' => ':attribute المحدد غير صحيح.',
    'file' => 'يجب أن يكون :attribute ملفاً.',
    'filled' => 'يجب ألا يكون حقل :attribute فارغاً.',
    'gt' => [
        'numeric' => 'يجب أن تكون قيمة :attribute أكبر من :value.',
        'file' => 'يجب أن يكون حجم :attribute أكبر من :value كيلوبايت.',
        'string' => 'يجب أن يكون طول :attribute أكبر من :value حرفاً.',
        'array' => 'يجب أن يحتوي :attribute على أكثر من :value عنصراً.',
    ],
    'gte' => [
        'numeric' => 'يجب أن تكون قيمة :attribute أكبر من أو تساوي :value.',
        'file' => 'يجب أن يكون حجم :attribute أكبر من أو يساوي :value كيلوبايت.',
        'string' => 'يجب أن يكون طول :attribute أكبر من أو يساوي :value حرفاً.',
        'array' => 'يجب أن يحتوي :attribute على :value عنصراً أو أكثر.',
    ],
    'image' => 'يجب أن يكون :attribute صورة.',
    'in' => ':attribute المحدد غير صحيح.',
    'in_array' => 'حقل :attribute غير موجود في :other.',
    'integer' => 'يجب أن يكون :attribute عدداً صحيحاً.',
    'ip' => 'يجب أن يكون :attribute عنوان IP صحيحاً.',
    'ipv4' => 'يجب أن يكون :attribute عنوان IPv4 صحيحاً.',
    'ipv6' => 'يجب أن يكون :attribute عنوان IPv6 صحيحاً.',
    'json' => 'يجب أن يكون :attribute نص JSON صحيحاً.',
    'lt' => [
        'numeric' => 'يجب أن تكون قيمة :attribute أقل من :value.',
        'file' => 'يجب أن يكون حجم :attribute أقل من :value كيلوبايت.',
        'string' => 'يجب أن يكون طول :attribute أقل من :value حرفاً.',
        'array' => 'يجب أن يحتوي :attribute على أقل من :value عنصراً.',
    ],
    'lte' => [
        'numeric' => 'يجب أن تكون قيمة :attribute أقل من أو تساوي :value.',
        'file' => 'يجب أن يكون حجم :attribute أقل من أو يساوي :value كيلوبايت.',
        'string' => 'يجب أن يكون طول :attribute أقل من أو يساوي :value حرفاً.',
        'array' => 'يجب ألا يحتوي :attribute على أكثر من :value عنصراً.',
    ],
    'max' => [
        'numeric' => 'يجب ألا تكون قيمة :attribute أكبر من :max.',
        'file' => 'يجب ألا يكون حجم :attribute أكبر من :max كيلوبايت.',
        'string' => 'يجب ألا يكون طول :attribute أكبر من :max حرفاً.',
        'array' => 'يجب ألا يحتوي :attribute على أكثر من :max عنصراً.',
    ],
    'mimes' => 'يجب أن يكون :attribute ملفاً من نوع: :values.',
    'mimetypes' => 'يجب أن يكون :attribute ملفاً من نوع: :values.',
    'min' => [
        'numeric' => 'يجب أن تكون قيمة :attribute على الأقل :min.',
        'file' => 'يجب أن يكون حجم :attribute على الأقل :min كيلوبايت.',
        'string' => 'يجب ألا يقل طول :attribute عن :min حرفاً.',
        'array' => 'يجب أن يحتوي :attribute على :min عنصراً على الأقل.',
    ],
    'not_in' => ':attribute المحدد غير صحيح.',
    'not_regex' => 'صيغة :attribute غير صالحة.',
    'numeric' => 'يجب أن يكون :attribute رقماً.',
    'password' => 'كلمة المرور غير صحيحة.',
    'present' => 'يجب أن يكون حقل :attribute موجوداً.',
    'prohibited' => 'حقل :attribute غير مسموح به.',
    'prohibited_if' => 'حقل :attribute غير مسموح به عندما تكون :other هي :value.',
    'prohibited_unless' => 'حقل :attribute غير مسموح به إلا عندما تكون :other إحدى :values.',
    'regex' => 'صيغة :attribute غير صالحة.',
    'required' => 'حقل :attribute مطلوب.',
    'required_if' => 'حقل :attribute مطلوب عندما تكون :other هي :value.',
    'required_unless' => 'حقل :attribute مطلوب إلا إذا كانت :other إحدى :values.',
    'required_with' => 'حقل :attribute مطلوب عند وجود :values.',
    'required_with_all' => 'حقل :attribute مطلوب عند وجود :values.',
    'required_without' => 'حقل :attribute مطلوب عند عدم وجود :values.',
    'required_without_all' => 'حقل :attribute مطلوب عند عدم وجود أي من :values.',
    'same' => 'يجب أن يتطابق :attribute و :other.',
    'size' => [
        'numeric' => 'يجب أن تكون قيمة :attribute مساوية لـ :size.',
        'file' => 'يجب أن يكون حجم :attribute :size كيلوبايت.',
        'string' => 'يجب أن يكون طول :attribute :size حرفاً.',
        'array' => 'يجب أن يحتوي :attribute على :size عنصراً.',
    ],
    'starts_with' => 'يجب أن يبدأ :attribute بأحد التالي: :values.',
    'string' => 'يجب أن يكون :attribute نصاً.',
    'timezone' => ':attribute ليست منطقة زمنية صحيحة.',
    'unique' => 'حقل :attribute مستخدم من قبل.',
    'uploaded' => 'فشل رفع :attribute.',
    'url' => 'صيغة :attribute غير صالحة.',
    'uuid' => 'يجب أن يكون :attribute معرف UUID صحيحاً.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    'classroom_belongs_to_grade' => 'الصف الدراسي المختار لا ينتمي إلى المرحلة الدراسية المختارة.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    */

    'attributes' => [
        'name' => 'الاسم',
        'first_name' => 'الاسم الأول',
        'last_name' => 'اسم العائلة',
        'middle_name' => 'الاسم الأوسط',
        'email' => 'البريد الإلكتروني',
        'password' => 'كلمة المرور',
        'password_confirmation' => 'تأكيد كلمة المرور',
        'current_password' => 'كلمة المرور الحالية',
        'phone' => 'رقم الهاتف',
        'address' => 'العنوان',
        'gender' => 'الجنس',
        'nationality' => 'الجنسية',
        'birth_date' => 'تاريخ الميلاد',
        'national_id' => 'رقم الهوية',
        'grade_id' => 'الصف الدراسي',
        'classroom_id' => 'الفصل الدراسي',
        'section_id' => 'القسم',
        'school_id' => 'المدرسة',
        'level_id' => 'المرحلة الدراسية',
        'subject_id' => 'المادة',
        'teacher_id' => 'المعلم',
        'student_id' => 'الطالب',
        'parent_id' => 'ولي الأمر',
        'class_id' => 'الفصل',
        'role_id' => 'الدور',
        'role' => 'الدور',
        'amount' => 'المبلغ',
        'date' => 'التاريخ',
        'time' => 'الوقت',
        'status' => 'الحالة',
        'type' => 'النوع',
        'title' => 'العنوان',
        'description' => 'الوصف',
        'image' => 'الصورة',
        'file' => 'الملف',
        'notes' => 'الملاحظات',
        'comment' => 'التعليق',
        'percentage' => 'النسبة',
        'mark' => 'الدرجة',
        'total' => 'الإجمالي',
        'year' => 'السنة',
        'month' => 'الشهر',
        'day' => 'اليوم',
        'start_date' => 'تاريخ البداية',
        'end_date' => 'تاريخ النهاية',
        'start_time' => 'وقت البداية',
        'end_time' => 'وقت النهاية',
        'capacity' => 'السعة',
        'question' => 'السؤال',
        'answer' => 'الإجابة',
        'options' => 'الخيارات',
        'language' => 'اللغة',
        'timezone' => 'المنطقة الزمنية',
        'url' => 'الرابط',
        'reason' => 'السبب',
        'value' => 'القيمة',
        'subject_ids' => 'المواد',
        'student_ids' => 'الطلاب',
        'teacher_ids' => 'المعلمون',
    ],

];