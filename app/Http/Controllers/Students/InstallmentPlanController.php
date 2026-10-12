<?php

namespace App\Http\Controllers\Students;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInstallmentPlanRequest;
use App\Http\Requests\UpdateInstallmentPlanRequest;
use App\Repository\InstallmentPlanRepositoryInterface;
use Illuminate\Http\Request;

class InstallmentPlanController extends Controller
{
    protected $installmentPlanRepository;

    public function __construct(InstallmentPlanRepositoryInterface $installmentPlanRepository)
    {
        $this->installmentPlanRepository = $installmentPlanRepository;
    }

    public function index()
    {
        $plans = $this->installmentPlanRepository->index();
        return view('pages.InstallmentPlans.index', compact('plans'));
    }

    public function create()
    {
        return view('pages.InstallmentPlans.create');
    }

    public function store(StoreInstallmentPlanRequest $request)
    {
        try {
            $this->installmentPlanRepository->store($request);
            toastr()->success(trans('messages.success'));
            return redirect()->route('installment-plans.index');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function show($id)
    {
        $plan = $this->installmentPlanRepository->show($id);
        return view('pages.InstallmentPlans.show', compact('plan'));
    }

    public function edit($id)
    {
        $plan = $this->installmentPlanRepository->edit($id);
        return view('pages.InstallmentPlans.edit', compact('plan'));
    }

    public function update(UpdateInstallmentPlanRequest $request, $id)
    {
        try {
            $this->installmentPlanRepository->update($request, $id);
            toastr()->success(trans('messages.Update'));
            return redirect()->route('installment-plans.index');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        try {
            $this->installmentPlanRepository->destroy($id);
            toastr()->error(trans('messages.Delete'));
            return redirect()->back();
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function generate($id)
    {
        try {
            $this->installmentPlanRepository->generate($id);
            toastr()->success(trans('messages.success'));
            return redirect()->back();
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
