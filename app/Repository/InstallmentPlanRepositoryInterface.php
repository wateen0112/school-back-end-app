<?php

namespace App\Repository;

interface InstallmentPlanRepositoryInterface
{
    public function index();

    public function show($id);

    public function create();

    public function store($request);

    public function edit($id);

    public function update($request, $id);

    public function destroy($id);

    public function generate($id);
}
