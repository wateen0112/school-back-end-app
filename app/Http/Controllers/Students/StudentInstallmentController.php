<?php

namespace App\Http\Controllers\Students;

use App\Http\Controllers\Controller;
use App\Repository\StudentInstallmentRepositoryInterface;
use Illuminate\Http\Request;

class StudentInstallmentController extends Controller
{
    protected $studentInstallmentRepository;

    public function __construct(StudentInstallmentRepositoryInterface $studentInstallmentRepository)
    {
        $this->studentInstallmentRepository = $studentInstallmentRepository;
    }

    public function index(Request $request)
    {
        $installments = $this->studentInstallmentRepository->index($request);
        return view('pages.StudentInstallments.index', compact('installments'));
    }

    public function show($id)
    {
        $installment = $this->studentInstallmentRepository->show($id);
        return view('pages.StudentInstallments.show', compact('installment'));
    }
}
