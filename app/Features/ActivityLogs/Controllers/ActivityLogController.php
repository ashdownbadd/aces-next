<?php

declare(strict_types=1);

namespace App\Features\ActivityLogs\Controllers;

use App\Features\ActivityLogs\Services\ActivityLogService;
use App\Foundation\View;
use App\Http\Request;
use App\Http\Response;

final class ActivityLogController
{
    public function __construct(
        private readonly View $view,
        private readonly ActivityLogService $activityLogs,
    ) {}

    /**
     * Display one activity log in detail.
     */
    public function show(Request $request, string $id): Response
    {
        $logId = (int) $id;

        if ($logId <= 0) {
            return Response::redirect('/activity-logs');
        }

        $log = $this->activityLogs->find($logId);

        if ($log === null) {
            return Response::redirect('/activity-logs');
        }

        return new Response(
            $this->view->render(
                'activity_logs.show',
                [
                    'title' => 'Activity Log Details',
                    'log' => $log,
                ],
                'layouts.app',
            ),
        );
    }

    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $action = trim((string) $request->query('action', ''));
        $userId = max(0, (int) $request->query('user', '0'));
        $dateFrom = trim((string) $request->query('date_from', ''));
        $dateTo = trim((string) $request->query('date_to', ''));

        $page = max(1, (int) $request->query('page', '1'));
        $perPage = 25;

        $totalLogs = $this->activityLogs->count(
            $search,
            $action,
            $userId,
            $dateFrom !== '' ? $dateFrom : null,
            $dateTo !== '' ? $dateTo : null,
        );

        $totalPages = max(1, (int) ceil($totalLogs / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        $logs = $this->activityLogs->all(
            $search,
            $action,
            $userId,
            $dateFrom !== '' ? $dateFrom : null,
            $dateTo !== '' ? $dateTo : null,
            $perPage,
            $offset,
        );

        return new Response(
            $this->view->render(
                'activity_logs.index',
                [
                    'title' => 'Activity Logs',
                    'logs' => $logs,
                    'actions' => $this->activityLogs->actions(),
                    'users' => $this->activityLogs->users(),
                    'search' => $search,
                    'action' => $action,
                    'userId' => $userId,
                    'dateFrom' => $dateFrom,
                    'dateTo' => $dateTo,
                    'currentPage' => $page,
                    'perPage' => $perPage,
                    'totalLogs' => $totalLogs,
                    'totalPages' => $totalPages,
                    'from' => $totalLogs > 0 ? $offset + 1 : 0,
                    'to' => $totalLogs > 0
                        ? min($offset + count($logs), $totalLogs)
                        : 0,
                ],
                'layouts.app',
            ),
        );
    }
}
