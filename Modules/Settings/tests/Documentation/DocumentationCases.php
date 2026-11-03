<?php

namespace Modules\Settings\Tests\Documentation;

use App\Classes\TestCases\TestCaseBuilder;
use Illuminate\Support\Facades\Http;
use Modules\Settings\Enums\SettingsType;
use Modules\Settings\Models\Settings;

class DocumentationCases
{
    /**
     * Define the documentation test cases for this module.
     * Use the TestCaseBuilder for a fluent definition.
     */
    public static function cases(): array
    {

        $enums = SettingsType::cases();
        $createIndex = [];
        $showUpdateDelete = [];
        $fakeLogin = fakeLogin();
        $expectKeys = ['id', 'name'];
        foreach ($enums as $enum) {

            if (in_array($enum, [
                SettingsType::Setting_PAGE,
                SettingsType::NATIONALITY,
                SettingsType::COUNTRY,
                SettingsType::CITY,
            ], true)) {
                continue;
            }

            $nameOfEnum = ucwords(str_replace('-', ' ', $enum->value));

            $params = [
                'name_en' => 'Test Settings',
                'name_ar' => 'أختبار للاعدادات',
            ];

            $type = $enum->value;
            $title = $nameOfEnum;
            $paramForUpdate = $params;
            $paramForUpdate['id'] = Settings::where('type', $type)->first()?->id;
            $headers = [
                'Content-Type' => 'application/json',
                'X-API-Token' => apiKeyGenerator(),
            ];
            $headersForUpdate = $headers;
            // Create
            $createIndex[] = TestCaseBuilder::make('Create '.$title)
                ->post('/api/v1/settings/'.$type)
                ->headers($headers)
                ->auth($fakeLogin)
                ->params($params)
                ->expectStatus(200)
                ->inDocs('Create '.$title, 'Store a new '.$title.' entry', 3)
                ->build();

            // Index
            $createIndex[] = TestCaseBuilder::make('Get '.$title.' List')
                ->get('/api/v1/settings/'.$type)
                ->query(['page' => 1, 'per_page' => 10])
                ->headers($headers)
                ->auth($fakeLogin)
                ->expectStatus(200)
                ->expectKeys($expectKeys)
                ->description('This API retrieves a paginated list of '.$title.'.')
                ->inDocs('Get '.$title.' List', '', 1)
                ->build();
            // Show
            $showUpdateDelete[] = TestCaseBuilder::make('Show Single '.$title)
                ->get('/api/v1/settings/'.$type.'/'.$paramForUpdate['id'])
                ->headers($headersForUpdate)
                ->auth($fakeLogin)
                ->expectStatus(200)
                ->expectKeys(['id', 'name'])
                ->inDocs('Show Single '.$title, 'Get Single '.$title, 2)
                ->build();
            // Update
            $showUpdateDelete[] = TestCaseBuilder::make('Update '.$title)
                ->put('/api/v1/settings/'.$type.'/'.$paramForUpdate['id'])
                ->headers($headersForUpdate)
                ->auth($fakeLogin)
                ->params($paramForUpdate)
                ->expectStatus(200)
                ->inDocs('Update '.$title, 'Update '.$title.' entry', 4)
                ->build();

            // Switch Active
            $showUpdateDelete[] = TestCaseBuilder::make('Active Or InActive '.$title)
                ->patch('/api/v1/settings/'.$type.'/'.$paramForUpdate['id'])
                ->headers($headersForUpdate)
                ->auth($fakeLogin)
                ->params([])
                ->expectStatus(200)
                ->inDocs('Update '.$title, 'Update '.$title.' entry', 4)
                ->build();

            // Delete
            $showUpdateDelete[] = TestCaseBuilder::make('Delete '.$title)
                ->delete('/api/v1/settings/'.$type.'/'.$paramForUpdate['id'])
                ->headers($headersForUpdate)
                ->auth($fakeLogin)
                ->expectStatus(200)
                ->inDocs('Delete '.$title, 'Delete '.$title.' entry', 5)
                ->build();
        }

        $headers = [
            'Content-Type' => 'application/json',
            'X-API-Token' => apiKeyGenerator(),
        ];
        $fakeLogin = fakeLogin();

        $locationCases = [];

        $locationCases[] = TestCaseBuilder::make('Get Countries List')
            ->get('/api/v1/locations/country')
            ->headers($headers)
            ->auth($fakeLogin)
            ->expectStatus(200)
            ->description('Retrieve a list of all available countries')
            ->inDocs('Locations', 'Get Countries List', 1)
            ->build();

        $locationCases[] = TestCaseBuilder::make('Search Countries')
            ->get('/api/v1/locations/country/search')
            ->query(['keyword' => 'Egypt'])
            ->headers($headers)
            ->auth($fakeLogin)
            ->expectStatus(200)
            ->description('Search for countries by keyword')
            ->inDocs('Locations', 'Search Countries', 2)
            ->build();

        $locationCases[] = TestCaseBuilder::make('Get Cities List')
            ->get('/api/v1/locations/city')
            ->headers($headers)
            ->auth($fakeLogin)
            ->expectStatus(200)
            ->description('Retrieve a list of all available cities')
            ->inDocs('Locations', 'Get Cities List', 3)
            ->build();

        $locationCases[] = TestCaseBuilder::make('Search Cities')
            ->get('/api/v1/locations/city/search')
            ->query(['keyword' => 'Cairo'])
            ->headers($headers)
            ->auth($fakeLogin)
            ->expectStatus(200)
            ->description('Search for cities by keyword')
            ->inDocs('Locations', 'Search Cities', 4)
            ->build();

        $country = Settings::firstOrCreate(
            [
                'type' => strtolower(SettingsType::COUNTRY->value),
                'name_en' => 'Documentation country',
            ],
            [
                'name_ar' => 'دولة التوثيق',
                'is_active' => true,
            ]
        );

        $country = Settings::whereRaw('LOWER(type) = ?', ['country'])->first();
        if ($country) {
            $locationCases[] = TestCaseBuilder::make('Get Cities By country')
                ->get('/api/v1/locations/country/'.$country->id.'/cities')
                ->headers($headers)
                ->auth($fakeLogin)
                ->expectStatus(200)
                ->description('Retrieve all cities for a specific country')
                ->inDocs('Locations', 'Get Cities By Country', 5)
                ->build();
        }

        $locationCases[] = TestCaseBuilder::make('Get Nationalities List')
            ->get('/api/v1/locations/nationality')
            ->headers($headers)
            ->auth($fakeLogin)
            ->expectStatus(200)
            ->description('Retrieve a list of all available nationalities')
            ->inDocs('Locations', 'Get Nationalities List', 6)
            ->build();

        $locationTypes = ['country', 'city', 'nationality'];
        $locationTypeIds = [];
        $httpClient = Http::withHeaders([
            'Accept' => 'application/json',
            'X-API-Token' => apiKeyGenerator(),
        ])->withToken($fakeLogin);

        foreach ($locationTypes as $locType) {
            $response = $httpClient->get(config('app.url').'/api/v1/settings/'.$locType, [
                'page' => 1,
                'per_page' => 1,
            ]);
            if ($response->successful() && is_array($response->json('data')) && isset($response->json('data')[0]['id'])) {
                $locationTypeIds[$locType] = $response->json('data')[0]['id'];
            }
        }

        // Arabic labels for each location type so name_ar stays valid Arabic
        // (the Arabic-name field must not contain Latin letters).
        $locationArabicLabels = [
            'country' => 'دولة',
            'city' => 'مدينة',
            'nationality' => 'جنسية',
        ];

        foreach ($locationTypes as $locType) {
            $locationParams = [
                'name_en' => 'Test '.ucfirst($locType),
                'name_ar' => 'أختبار '.($locationArabicLabels[$locType] ?? $locType),
            ];

            if ($locType === 'city') {
                $countryId = $locationTypeIds['country'] ?? Settings::where('type', SettingsType::COUNTRY->value)->value('id');
                $locationParams['parent_id'] = $countryId;
            }

            if ($locType === 'country') {
                $locationParams['data'] = [
                    'code' => 'TC',
                    'phone_code' => '999',
                    'phone_length' => 9,
                ];
            }

            $existingLocationId = $locationTypeIds[$locType] ?? Settings::where('type', $locType)->value('id');

            $locationCases[] = TestCaseBuilder::make('Create '.$locType)
                ->post('/api/v1/settings/'.$locType)
                ->headers($headers)
                ->auth($fakeLogin)
                ->params($locationParams)
                ->expectStatus(200)
                ->inDocs('Locations', 'locations-store-setting', 7)
                ->build();

            if ($existingLocationId) {
                $locationCases[] = TestCaseBuilder::make('Update '.$locType)
                    ->put('/api/v1/settings/'.$locType.'/'.$existingLocationId)
                    ->headers($headers)
                    ->auth($fakeLogin)
                    ->params(array_merge($locationParams, ['id' => $existingLocationId]))
                    ->expectStatus(200)
                    ->inDocs('Locations', 'locations-update-setting', 8)
                    ->build();

                $locationCases[] = TestCaseBuilder::make('Toggle '.$locType)
                    ->patch('/api/v1/settings/'.$locType.'/'.$existingLocationId.'/toggle-active')
                    ->headers($headers)
                    ->auth($fakeLogin)
                    ->params([])
                    ->expectStatus(200)
                    ->inDocs('Locations', 'locations-toggle-active-setting', 9)
                    ->build();

                $locationCases[] = TestCaseBuilder::make('Delete '.$locType)
                    ->delete('/api/v1/settings/'.$locType.'/'.$existingLocationId)
                    ->headers($headers)
                    ->auth($fakeLogin)
                    ->expectStatus(200)
                    ->inDocs('Locations', 'locations-delete-setting', 10)
                    ->build();
            }
        }

        return [
            'create-index' => $createIndex,
            'show-update-delete' => $showUpdateDelete,
            'locations' => $locationCases,
        ];
    }
}
