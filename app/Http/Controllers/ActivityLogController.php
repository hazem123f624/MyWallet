<?php

namespace App\Http\Controllers;

use App\Http\Traits\HttpResponsesTrait;
use App\Services\ActivityLog\ActivityLogService;
use App\Services\ActivityLog\Requests\DownloadActivityLogExportRequest;
use App\Services\ActivityLog\Requests\ExportSubjectActivityLogRequest;
use App\Services\ActivityLog\Resources\ActivityLogExportResource;
use App\Services\ActivityLog\Resources\ActivityLogResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ActivityLogController extends Controller
{
    use HttpResponsesTrait;

    /**
     * Display a listing of activity logs.
     */
    public function index(Request $request)
    {
        $service = app(ActivityLogService::class);

        $logs = $service->getLogs(
            filters: $request->all(),
            perPage: $request->row_in_page ?? 15
        );

        return $this->success(
            message: 'Success',
            data: ActivityLogResource::collection($logs),
            appends: [
                'pagination' => [
                    'per_page' => $logs->perPage(),
                    'current_page' => $logs->currentPage(),
                    'total' => $logs->total(),
                    'last_page' => $logs->lastPage(),
                ],
            ]
        );
    }

    /**
     * Display activity logs for a specific subject.
     */
    public function subject(int $subjectId)
    {
        $service = app(ActivityLogService::class);

        $filters = ['subject_id' => $subjectId];

        if (request()->filled('subject_type')) {
            $filters['subject_type'] = request('subject_type');
        }

        $logs = $service->getLogs(
            filters: $filters,
            perPage: (int) (request()->row_in_page ?? 15)
        );

        return $this->success(
            message: 'Success',
            data: ActivityLogResource::collection($logs),
            appends: [
                'pagination' => [
                    'per_page' => $logs->perPage(),
                    'current_page' => $logs->currentPage(),
                    'total' => $logs->total(),
                    'last_page' => $logs->lastPage(),
                ],
            ]
        );
    }

    /**
     * Store the chosen subject's activity logs as a downloadable CSV file.
     */
    public function exportSubject(ExportSubjectActivityLogRequest $request)
    {
        $service = app(ActivityLogService::class);

        $export = $service->exportSubjectCsv(
            subjectId: $request->validated('subject_id')
        );

        return new ActivityLogExportResource($export);
    }

    /**
     * Download an exported CSV file and delete it after it is sent.
     */
    public function downloadExport(DownloadActivityLogExportRequest $request)
    {
        $service = app(ActivityLogService::class);

        $fileName = $request->validated('file_name');
        $path = $service->exportFilePath($fileName);

        if (! Storage::disk('public')->exists($path)) {
            return $this->failure('Export file not found.');
        }

        $disk = Storage::disk('public');
        $content = $disk->get($path);
        $disk->delete($path);

        return response($content, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
        ]);
    }
}
