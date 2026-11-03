<?php

namespace App\Http\Traits;

use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

trait HttpResponsesTrait
{
    protected function success($message, $data = [], $status = Response::HTTP_OK, $appends = [])
    {
        [$data,$appends] = $this->handelDataBeforeSend($data, $appends);
        $return = [
            'data' => $data,
            'message' => __($message),
            'success' => true,
        ];
        if (! empty($appends)) {
            foreach ($appends as $key => $value) {
                $return[$key] = $value;
            }
        }

        return response($return, $status);
    }

    public function failure($message, $status = ResponseAlias::HTTP_BAD_REQUEST)
    {
        return response([
            'success' => false,
            'message' => __($message),
        ], $status);
    }

    /**
     * Error response carrying an optional data payload (e.g. a conflict detail or
     * an eligibility table).
     */
    public function error($message, $status = ResponseAlias::HTTP_BAD_REQUEST, $data = [])
    {
        return response()->json([
            'success' => false,
            'message' => __($message),
            'data' => $data,
        ], $status);
    }

    public function errors($message, $data = [], $status = Response::HTTP_UNPROCESSABLE_ENTITY, $project = 'api')
    {
        $dataAfterTranslate = [];
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $dataAfterTranslate[$key] = [];
                foreach ($value as $v) {
                    $dataAfterTranslate[$key][] = __($v);
                }
            }
        } else {
            $dataAfterTranslate = __($data);
        }

        return response([
            'success' => false,
            'message' => __($message),
            'errors' => $dataAfterTranslate,
        ], $status);
    }

    private function handelDataBeforeSend($data, $appends): array
    {
        $newData = [];
        $isDataArray = is_array($data);
        
        if (($isDataArray || $data instanceof \Countable) && count($data) > 1 && $isDataArray && array_key_exists('data', $data)) {
            $newData = $data['data'];
            unset($data['data']);
            $appends = array_merge($appends, $data);
        } else {
            $newData = ($isDataArray && array_key_exists('data', $data)) ? $data['data'] : $data;
        }

        return [$newData, $appends];
    }
}
