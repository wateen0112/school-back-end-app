<?php

// =====================================================================
//  Tinker script: assign real Arabic full names to EXISTING students
//  It only UPDATES existing rows (no new students are created).
//
//  Run it with:
//     get-content assign_arabic_student_names.php -Raw | php artisan tinker
//  or inside tinker:
//     php artisan tinker
//     then: require 'assign_arabic_student_names.php';
//
//  The student name column is translatable JSON (spatie),
//  so both the Arabic (ar) and the English transliteration (en)
//  parts are written, exactly like the controller does.
// =====================================================================

use App\Models\Student;

// ---------- Name pools -------------------------------------------------

// Male first names (Arabic => transliteration)
$maleAr = ['أحمد','محمد','علي','عمر','خالد','يوسف','إبراهيم','مصطفى','محمود','حسن','حسين','طارق','سامي','رامي','مازن','عمرو','أيمن','فادي','بلال','زيد','حمزة','أنس','معاذ','صلاح','وليد','كريم','سعيد','عماد','حاتم','جمال','زياد','عادل','أشرف','منير','نبيل','هشام'];
$maleEn = ['Ahmed','Mohamed','Ali','Omar','Khaled','Youssef','Ibrahim','Mostafa','Mahmoud','Hassan','Hussein','Tarek','Samy','Ramy','Mazen','Amr','Ayman','Fady','Bilal','Zaid','Hamza','Anas','Moaz','Salah','Walid','Karim','Saeed','Emad','Hatem','Gamal','Ziad','Adel','Ashraf','Mounir','Nabil','Hesham'];

// Female first names (Arabic => transliteration)
$femaleAr = ['فاطمة','مريم','خديجة','عائشة','زينب','سارة','نور','رهف','لينا','أمل','هدى','رانيا','دعاء','إيمان','سلمى','ياسمين','ملك','شهد','ريم','غادة','دينا','هالة','منى','سعاد','ليلى','رنا','أريج','بثينة','سمية','آلاء','حنان','إسراء','عبير','مروة','إيناس','نسمة'];
$femaleEn = ['Fatma','Mariam','Khadija','Aisha','Zainab','Sara','Nour','Rahaf','Lina','Amal','Hoda','Rania','Doaa','Iman','Salma','Yasmin','Malak','Shahd','Reem','Ghada','Dina','Hala','Mona','Soad','Laila','Rana','Areej','Buthaina','Somaya','Alaa','Hanan','Esraa','Abeer','Marwa','Einas','Nesma'];

// Father / family names (Arabic => transliteration)
$familyAr = ['السيد','الخطيب','الحداد','عبد الله','رمضان','سليمان','الشامي','مراد','عواد','فرحات','قاسم','الديب','عثمان','صبري','الكردي'];
$familyEn = ['ElSayed','ElKhatib','ElHaddad','Abdallah','Ramadan','Suleiman','ElShamy','Murad','Awad','Farahat','Kassem','ElDeeb','Othman','Sabry','ElKordy'];

// ---------- Process every existing student -----------------------------

$maleCounter = 0;
$femaleCounter = 0;
$updated = 0;

$students = Student::withTrashed()->orderBy('id')->get();

foreach ($students as $student) {
    $id = (int) $student->id;
    $isMale = (int) $student->gender_id === 3;

    if ($isMale) {
        $firstAr = $maleAr[$maleCounter % count($maleAr)];
        $firstEn = $maleEn[$maleCounter % count($maleEn)];
        $maleCounter++;
    } else {
        $firstAr = $femaleAr[$femaleCounter % count($femaleAr)];
        $firstEn = $femaleEn[$femaleCounter % count($femaleEn)];
        $femaleCounter++;
    }

    $familyArName = $familyAr[$id % count($familyAr)];
    $familyEnName = $familyEn[$id % count($familyEn)];

    $fullAr = $firstAr . ' ' . $familyArName;
    $fullEn = $firstEn . ' ' . $familyEnName;

    $student->name = ['en' => $fullEn, 'ar' => $fullAr];
    $student->save();

    $updated++;
    echo 'Updated #' . $id . ' => ' . $fullAr . PHP_EOL;
}

echo '----------------------------------------------------------------' . PHP_EOL;
echo 'Total existing students updated: ' . $updated . PHP_EOL;

// =====================================================================
//  OPTIONAL: edit a single student's Arabic name by ID
//  Uncomment and adjust the id + name below, then re-run the script.
// =====================================================================
// $one = Student::find(101);
// if ($one) {
//     $one->name = ['en' => 'Ahmed Mohamed', 'ar' => 'أحمد محمد'];
//     $one->save();
//     echo 'Edited #' . $one->id . ' => ' . $one->name . PHP_EOL;
// }