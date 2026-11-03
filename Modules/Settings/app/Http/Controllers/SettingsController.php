<?php

namespace Modules\Settings\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Traits\HttpResponsesTrait;
use Illuminate\Http\Request;
use Modules\Settings\Http\Requests\SettingsRequestFactory;
use Modules\Settings\Interfaces\SettingsInterface;
use Modules\Settings\Models\Settings;
use Modules\Settings\Resources\SettingsResource;
use Symfony\Component\HttpFoundation\Response;

class SettingsController extends Controller
{
    use HttpResponsesTrait;

    private SettingsInterface $repository;

    public function __construct(SettingsInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index($type)
    {
        $requests = request()->all();
        $requests['type'] = $type;
        $request = $this->handelRequests(requests: $requests);
        $data = $this->repository->data(request: $request);
        $data['data'] = SettingsResource::collection($data['data']);

        return $this->success('Data retrieved successfully.', data: $data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, $type)
    {
        $requestValidated = SettingsRequestFactory::validate(request: $request, type: $type);
        $requestValidated['setting_id'] = (array_key_exists('parent_id', $requestValidated)) ? $requestValidated['parent_id'] : null;
        unset($requestValidated['parent_id']);
        $data = $this->repository->save($requestValidated);

        return $this->success('Data inserted successfully.', data: $data);
    }

    /**
     * Show the specified resource.
     */
    public function show($type, Settings $id)
    {
        $requests = request()->all();
        $requests['type'] = $type;
        $request = $this->handelRequests(requests: $requests);
        $data = $this->repository->data($request, $id);
        $data = new SettingsResource($data);

        return $this->success('Data retrieved successfully.', data: $data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $type, $id)
    {
        $requestValidated = SettingsRequestFactory::validate(request: $request, type: $type, id: $id);
        $requestValidated['setting_id'] = (array_key_exists('parent_id', $requestValidated)) ? $requestValidated['parent_id'] : null;
        unset($requestValidated['parent_id']);
        $data = $this->repository->save($requestValidated, $id);

        return $this->success('Data updated successfully.', data: $data);
    }

    public function switchActive(Request $request, $type, $id)
    {
        $data = $this->repository->switchActive($id);
        $data = new SettingsResource($data);

        return $this->success('Data updated successfully.', data: $data);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($type, Settings $data)
    {
        $return = $this->repository->delete($data);

        return $this->success('Data deleted successfully.', data: []);
    }

    public function countries()
    {
        return $this->success(
            'Data retrieved successfully.',
            SettingsResource::collection(
                $this->repository->countries()
            )
        );
    }

    public function cities()
    {
        return $this->success(
            'Data retrieved successfully.',
            SettingsResource::collection(
                $this->repository->cities()
            )
        );
    }

    public function nationalities()
    {
        return $this->success(
            'Data retrieved successfully.',
            SettingsResource::collection(
                $this->repository->nationalities()
            )
        );
    }

    public function citiesByCountry($countryId)
    {
        return $this->success(
            'Data retrieved successfully.',
            SettingsResource::collection(
                $this->repository->citiesByCountry($countryId)
            )
        );
    }

    public function countryByCity($cityId)
    {
        $country = $this->repository->countryByCity($cityId);

        if (! $country) {
            return $this->failure('Country not found for this city.', Response::HTTP_NOT_FOUND);
        }

        return $this->success(
            'Data retrieved successfully.',
            new SettingsResource($country)
        );
    }

    public function searchCities(Request $request)
    {
        $data = $this->repository->cities($request->keyword);

        return $this->success(
            'Data retrieved successfully.',
            SettingsResource::collection($data)
        );
    }

    public function searchCountries(Request $request)
    {
        $data = $this->repository->countries($request->keyword);

        return $this->success(
            'Data retrieved successfully.',
            SettingsResource::collection($data)
        );
    }
}
