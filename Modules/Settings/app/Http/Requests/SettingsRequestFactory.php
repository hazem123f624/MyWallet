<?php

namespace Modules\Settings\Http\Requests;

use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;

class SettingsRequestFactory
{
    // eggs-health-status
    public static function validate(Request $request, $type, $id = null)
    {
        $folder = $id ? 'Update' : 'Create';
        $className = str_replace(' ', '', ucwords(str_replace('-', ' ', $type))).'Request';
        $fullClassName = "Modules\\Settings\\Http\\Requests\\Types\\$folder\\$className";

        if (! class_exists($fullClassName)) {
            throw new \Exception("Validation class not found for type: {$type} in {$folder}");
        }

        $formRequest = $fullClassName::createFrom($request);

        // Merge route parameters into the request so validation rules can find them
        //        $formRequest->merge([
        //            'id'   => $id,
        //            'type' => $type
        //        ]);

        // Inject container and redirector
        $formRequest->setContainer(app());
        $formRequest->setRedirector(app(Redirector::class));
        $formRequest->validateResolved();
        $returnData = $formRequest->validated();
        $returnData['type'] = $type;

        return $returnData;
    }
}
