<?php

namespace Modules\Settings\Repositories;

use App\Services\ForRepository\RepositoryService;
use Illuminate\Support\Facades\DB;
use Modules\Settings\Enums\SettingsType;
use Modules\Settings\Interfaces\SettingsInterface;
use Modules\Settings\Models\Settings;

class SettingsRepository implements SettingsInterface
{
    public object $model;

    public RepositoryService $serviceHelper;

    public array $files = [];

    public array $images = [];

    public array $multiRecord = [];

    public array $relatedTables = [];

    public function __construct(Settings $model)
    {
        $this->model = $model;
        $this->serviceHelper = new RepositoryService($this);
    }

    public function data($request, $id = '*', $fieldName = '', $collectionType = 'Model')
    {
        return $this->serviceHelper->handelForGetData(request: $request, id: $id, fieldName: $fieldName, collectionType: $collectionType);
    }

    public function save($request, $id = null)
    {
        try {
            DB::beginTransaction();
            if ($id && $id != $request['id']) {
                throw new \Exception('your id is not match');
            }
            [$request , $requestAfterRemoveTranslatedAttributes] = $this->serviceHelper->handle(request: $request, id: $id);
            $data = $this->model->updateOrCreate(['id' => $id], $requestAfterRemoveTranslatedAttributes);
            $this->serviceHelper->handelAfterCreated(request: $request, data: $data, id: $data->id);
            DB::commit();

            return $data;
        } catch (\Exception $exception) {
            DB::rollBack();
            throw new \Exception($exception->getMessage());
        }
    }

    public function switchActive($id)
    {
        try {
            $data = $this->model->findOrFail($id);
            $data->is_active = ! $data->is_active;
            $data->save();

            return $data;
        } catch (\Exception $exception) {
            DB::rollBack();
            throw new \Exception($exception->getMessage());
        }

    }

    public function delete($data): void
    {
        try {
            $this->serviceHelper->handelBeforeDeleted($data->id);
            $data->delete();
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }
    }

    public function countries($keyword = null)
    {
        $keyword ??= request('search');

        return Settings::query()
            ->where('type', SettingsType::COUNTRY->value)
            ->when($keyword !== null && $keyword !== '', function ($query) use ($keyword): void {
                $query->where(function ($q) use ($keyword): void {
                    $q->where('name_ar', 'like', "%{$keyword}%")
                        ->orWhere('name_en', 'like', "%{$keyword}%");
                });
            })
            ->get();
    }

    public function cities($keyword = null)
    {
        $keyword ??= request('search');

        return Settings::query()
            ->countries()
            ->with([
                'cities' => function ($query) use ($keyword): void {
                    if ($keyword !== null && $keyword !== '') {
                        $query->where(function ($q) use ($keyword): void {
                            $q->where('name_ar', 'like', "%{$keyword}%")
                                ->orWhere('name_en', 'like', "%{$keyword}%");
                        });
                    }
                },
            ])
            ->get();
    }

    public function nationalities($keyword = null)
    {
        $keyword ??= request('search');

        return Settings::query()
            ->where('type', SettingsType::NATIONALITY->value)
            ->when($keyword !== null && $keyword !== '', function ($query) use ($keyword): void {
                $query->where(function ($q) use ($keyword): void {
                    $q->where('name_ar', 'like', "%{$keyword}%")
                        ->orWhere('name_en', 'like', "%{$keyword}%");
                });
            })
            ->get();
    }

    public function citiesByCountry($countryId)
    {
        return Settings::query()
            ->cities()
            ->where('setting_id', $countryId)
            ->get();
    }

    public function countryByCity($cityId)
    {
        $city = Settings::withTrashed()
            ->cities()
            ->where('id', $cityId)
            ->first();

        if (! $city || ! $city->setting_id) {
            return null;
        }

        return Settings::withTrashed()->find($city->setting_id);
    }
}
