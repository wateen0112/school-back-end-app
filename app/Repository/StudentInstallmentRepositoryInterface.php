<?php

namespace App\Repository;

use App\Models\StudentInstallment;
use Illuminate\Http\Request;

interface StudentInstallmentRepositoryInterface
{
    public function index($request);

    public function show($id);

    public function myInstallments(Request $request);

    public function updatePaymentStatus(StudentInstallment $studentInstallment);
}
