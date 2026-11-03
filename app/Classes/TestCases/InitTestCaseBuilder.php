<?php

namespace App\Classes\TestCases;

use App\Services\Documentations\DocumentationService;

class InitTestCaseBuilder
{
    public function handle($type,$name,$route,$params,$auth,$desc,$shortDesc,$notes = [],$expectStatus=200,$group = null){
        $apiKeyGenerator = apiKeyGenerator();
        $timeStamp = time();
        $type = strtoupper($type);
        $headers = [
            'Content-Type' => 'application/json',
            config('security.api_key.header_name') => $apiKeyGenerator,
        ];
        if (config('security.timestamp.enabled')){
            $headers[config('security.timestamp.header_name')] = $timeStamp;
        }
        if (config('security.signature.enabled')){
            $headers[config('security.signature.header_name')] = signatureKeyGenerator($type, $params, '/api/v1/'.$route, $timeStamp);
        }
        return $this->$type($name,$route,$params,$headers,$auth,$desc,$shortDesc,$expectStatus , $notes);
    }
    private function POST($name,$route,$params,$header,$auth,$desc,$shortDesc,$expectStatus , $notes)
    {
        $testCase = TestCaseBuilder::make($name);
        $testCase = $testCase->post('/api/v1/'.$route);
        $testCase = $testCase->params($params);
        $testCase = $testCase->expectStatus($expectStatus);
        $testCase = $testCase->headers($header);
        if($auth){
            $testCase = $testCase->auth($auth);
        }
        if (!empty($notes)){
            $notes = app(DocumentationService::class)->generateValidationDocs($notes);
        }
        $testCase = $testCase->notes($notes);
        // If params has 'files' key, use it for file attachments
//        $files = [];
//        if (isset($params['files']) && is_array($params['files'])) {
//            $files = $params['files'];
//            unset($params['files']);
//            $testCase = $testCase->params($params);
//            $testCase = $testCase->files($files);
//        } elseif (isset($params['image']) && is_string($params['image'])) {
//            $testCase = $testCase->files(['image']);
//        } elseif (isset($params['file_name']) && is_string($params['file_name'])) {
//            $testCase = $testCase->files([str_replace('_name', '', key($params))]);
//        }
        $testCase = $testCase->inDocs($shortDesc, $desc, 1);
        return $testCase->build();
    }
    private function GET($name,$route,$params,$header,$auth,$desc,$shortDesc,$expectStatus , $notes):array
    {
        $testCase = TestCaseBuilder::make($name);
        $testCase = $testCase->get('/api/v1/'.$route);
        $testCase = $testCase->expectStatus($expectStatus);
        $testCase = $testCase->headers($header);
        if($auth){
            $testCase = $testCase->auth($auth);
        }
        if (!empty($notes)){
            $notes = app(DocumentationService::class)->generateValidationDocs($notes);
        }
        $testCase = $testCase->notes($notes);
        if (!empty($params)) {
            if (array_is_list($params)) {
                $testCase = $testCase->expectKeys($params);
            } else {
                $testCase = $testCase->query($params);
            }
        }
        $testCase = $testCase->inDocs($shortDesc, $desc, 1);
        return $testCase->build();
    }

    private function DELETE($name,$route,$params,$header,$auth,$desc,$shortDesc,$expectStatus , $notes):array
    {
        $testCase = TestCaseBuilder::make($name);
        $testCase = $testCase->delete('/api/v1/'.$route);
        $testCase = $testCase->expectStatus($expectStatus);
        $testCase = $testCase->headers($header);
        if($auth){
            $testCase = $testCase->auth($auth);
        }
        if (!empty($notes)){
            $notes = app(DocumentationService::class)->generateValidationDocs($notes);
        }
        $testCase = $testCase->notes($notes);
        $testCase = $testCase->inDocs($shortDesc, $desc, 1);
        return $testCase->build();
    }
    private function PUT($name,$route,$params,$header,$auth,$desc,$shortDesc,$expectStatus , $notes):array
    {
        $testCase = TestCaseBuilder::make($name);
        $testCase = $testCase->put('/api/v1/'.$route);
        $testCase = $testCase->params($params);
        $testCase = $testCase->expectStatus($expectStatus);
        $testCase = $testCase->headers($header);
        if($auth){
            $testCase = $testCase->auth($auth);
        }
        if (!empty($notes)){
            $notes = app(DocumentationService::class)->generateValidationDocs($notes);
        }
        $testCase = $testCase->notes($notes);
//        // If params has 'files' key, use it for file attachments
//        if (isset($params['files']) && is_array($params['files'])) {
//            $files = $params['files'];
//            unset($params['files']);
//            $testCase = $testCase->params($params);
//            $testCase = $testCase->files($files);
//        } elseif (isset($params['image']) && is_string($params['image'])) {
//            $testCase = $testCase->files(['image']);
//        }
        $testCase = $testCase->inDocs($shortDesc, $desc, 1);
        return $testCase->build();
    }
    private function PATCH($name,$route,$params,$header,$auth,$desc,$shortDesc,$expectStatus, $notes):array
    {
        $testCase = TestCaseBuilder::make($name);
        $testCase = $testCase->patch('/api/v1/'.$route);
        $testCase = $testCase->params($params);
        $testCase = $testCase->expectStatus($expectStatus);
        $testCase = $testCase->headers($header);
        if($auth){
            $testCase = $testCase->auth($auth);
        }
        if (!empty($notes)){
            $notes = app(DocumentationService::class)->generateValidationDocs($notes);
        }
        $testCase = $testCase->notes($notes);
//        // If params has 'files' key, use it for file attachments
//        if (isset($params['files']) && is_array($params['files'])) {
//            $files = $params['files'];
//            unset($params['files']);
//            $testCase = $testCase->params($params);
//            $testCase = $testCase->files($files);
//        } elseif (isset($params['image']) && is_string($params['image'])) {
//            $testCase = $testCase->files(['image']);
//        }
        $testCase = $testCase->inDocs($shortDesc, $desc, 1);
        return $testCase->build();
    }
}
