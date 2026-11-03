<?php

namespace Modules\Admins\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Traits\HttpResponsesTrait;
use Modules\Admins\Http\Requests\CreateModelRequest;
use Modules\Admins\Http\Requests\UpdateModelRequest;
use Modules\Admins\Interfaces\ModelsInterface;
use Modules\Admins\Models\Models;
use Modules\Admins\Resources\ModelsResource;

class ModelsController extends Controller
{
    use HttpResponsesTrait;

    private ModelsInterface $repository;

    public function __construct(ModelsInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        $request = $this->handelRequests(requests: request()->all());
        $data = $this->repository->data(request: $request);
        $data['data'] = ModelsResource::collection($data['data']);

        return $this->success('Data retrieved successfully.', data: $data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateModelRequest $request) {}

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return $this->success('Data retrieved successfully.', data: []);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateModelRequest $request, $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Models $id) {}
}
