<?php

namespace App\Services\Documentations\DTOs;

use App\DTOs\BaseDto;

class DocumentationDTO extends BaseDto
{
    public function __construct(
        public $id,
        public $name,
        public $section,
        public $category,
        public $sorting,
        public $short_description,
        public $description,
        public $headers,
        public $notes,
        public $query_params,
        public $body,
        public $method,
        public $route,
        public $success_response,
        public $error_response,
        public $developer_tested,
        public $tester_tested,
        public $is_active,
        public $created_at
    ) {}

    public static function fromModel($model)
    {
        return new DocumentationDTO(
            $model->id,
            $model->name,
            $model->section,
            $model->category,
            $model->sorting,
            $model->short_description,
            $model->description,
            $model->headers,
            $model->notes,
            $model->query_params,
            $model->body,
            $model->method,
            $model->route,
            $model->success_response,
            $model->error_response,
            $model->developer_tested,
            $model->tester_tested,
            $model->is_active,
            date('Y-m-d H:i:s', strtotime($model->created_at)),
        );
    }

    public static function fromArray($model)
    {
        $row = [];
        $row['id'] = $model->id;
        $row['name'] = $model->name;
        $row['section'] = $model->section;
        $row['category'] = $model->category;
        $row['sorting'] = $model->sorting;
        $row['short_description'] = $model->short_description;
        $row['description'] = $model->description;
        $row['headers'] = $model->headers;
        $row['notes'] = $model->notes;
        $row['query_params'] = $model->query_params;
        $row['body'] = $model->body;
        $row['method'] = $model->method;
        $row['route'] = $model->route;
        $row['success_response'] = $model->success_response;
        $row['error_response'] = $model->error_response;
        $row['developer_tested'] = $model->developer_tested;
        $row['tester_tested'] = $model->tester_tested;
        $row['is_active'] = $model->is_active;
        $row['created_at'] = date('Y-m-d H:i:s', strtotime($model->created_at));

        return $row;
    }
}
