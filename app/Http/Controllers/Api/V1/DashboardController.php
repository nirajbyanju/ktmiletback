<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\AuthorizesRbacRequests;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends BaseController
{
    use AuthorizesRbacRequests;

    public function __construct(
        private readonly DashboardService $dashboardService
    ) {
    }

    public function summary(Request $request): JsonResponse
    {
        if ($response = $this->authorizeAnyAbility($request, ['view_dashboard', 'manage_all'])) {
            return $response;
        }

        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->summary(
                $request->user(),
                $this->period($request),
                $this->limit($request, 5)
            ),
        ]);
    }

    public function recentProperties(Request $request): JsonResponse
    {
        if ($response = $this->authorizeAnyAbility($request, ['view_dashboard', 'manage_all'])) {
            return $response;
        }

        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->recentProperties(
                $this->period($request),
                $this->limit($request, 10)
            ),
        ]);
    }

    public function recentActivity(Request $request): JsonResponse
    {
        if ($response = $this->authorizeAnyAbility($request, ['view_dashboard', 'manage_all'])) {
            return $response;
        }

        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->recentActivity($this->limit($request, 10)),
        ]);
    }

    public function performance(Request $request): JsonResponse
    {
        if ($response = $this->authorizeAnyAbility($request, ['view_dashboard', 'manage_all'])) {
            return $response;
        }

        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->performance($this->period($request)),
        ]);
    }

    public function report(Request $request): JsonResponse
    {
        if ($response = $this->authorizeAnyAbility($request, ['view_dashboard', 'manage_all'])) {
            return $response;
        }

        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->report(
                $request->user(),
                $this->period($request),
                $this->limit($request, 10)
            ),
        ]);
    }

    private function period(Request $request): string
    {
        $period = strtolower((string) $request->get('period', 'month'));

        return in_array($period, ['week', 'month', 'year'], true) ? $period : 'month';
    }

    private function limit(Request $request, int $default): int
    {
        $limit = (int) $request->get('limit', $default);

        return max(1, min($limit, 25));
    }
}
