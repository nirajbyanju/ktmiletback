<?php

namespace App\Services;

use App\Models\FieldVisits;
use App\Models\Inquery;
use App\Models\Property;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class DashboardService
{
    public function __construct(
        private readonly UserMenuService $userMenuService
    ) {
    }

    public function summary(User $user, string $period = 'month', int $limit = 5): array
    {
        $range = $this->resolvePeriod($period);
        $menus = $this->userMenuService->getForUser($user);
        $pendingInquiries = $this->pendingInquiries();

        $totalProperties = Property::count();
        $previousTotalProperties = Property::where('created_at', '<=', $range['previous_end'])->count();

        $activeListings = Property::where('is_status', 1)->count();
        $previousActiveListings = Property::where('is_status', 1)
            ->where('created_at', '<=', $range['previous_end'])
            ->count();

        $currentPeriodViews = (int) Property::whereBetween('updated_at', [$range['start'], $range['end']])->sum('views_count');
        $previousPeriodViews = (int) Property::whereBetween('updated_at', [$range['previous_start'], $range['previous_end']])->sum('views_count');

        return [
            'period' => $range['period'],
            'available_periods' => ['week', 'month', 'year'],
            'generated_at' => now()->toIso8601String(),
            'current_user' => [
                'id' => $user->id,
                'name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
                'roles' => $user->getRoleNames()->values(),
                'permissions_count' => $user->getAllPermissions()->count(),
                'visible_menu_count' => $this->countMenus($menus),
            ],
            'stats' => [
                'total_properties' => $this->buildTrendStat(
                    'Total Properties',
                    $totalProperties,
                    $this->formatNumber($totalProperties),
                    $this->calculateChange($totalProperties, $previousTotalProperties),
                    $range['comparison_label']
                ),
                'active_listings' => $this->buildTrendStat(
                    'Active Listings',
                    $activeListings,
                    $this->formatNumber($activeListings),
                    $this->calculateChange($activeListings, $previousActiveListings),
                    $range['comparison_label']
                ),
                'pending_deals' => [
                    'label' => 'Pending Deals',
                    'value' => $pendingInquiries->count(),
                    'formatted_value' => $this->formatNumber($pendingInquiries->count()),
                    'urgent_count' => $pendingInquiries->filter(fn (Inquery $inquiry) => $this->isUrgentInquiry($inquiry))->count(),
                    'subtitle' => $pendingInquiries->filter(fn (Inquery $inquiry) => $this->isUrgentInquiry($inquiry))->count() . ' urgent need attention',
                ],
                'monthly_views' => $this->buildTrendStat(
                    'Monthly Views',
                    $currentPeriodViews,
                    $this->formatNumber($currentPeriodViews),
                    $this->calculateChange($currentPeriodViews, $previousPeriodViews),
                    $range['versus_label']
                ),
            ],
            'quick_actions' => [
                [
                    'key' => 'add_property',
                    'label' => 'Add Property',
                    'type' => 'route',
                    'target' => '/admin/property/create',
                ],
                [
                    'key' => 'schedule_showing',
                    'label' => 'Schedule Showing',
                    'type' => 'route',
                    'target' => '/admin/fieldVisit',
                ],
                [
                    'key' => 'view_inquiries',
                    'label' => 'View Inquiries',
                    'type' => 'route',
                    'target' => '/admin/propertyInquery',
                    'badge_count' => $pendingInquiries->count(),
                ],
                [
                    'key' => 'generate_report',
                    'label' => 'Generate Report',
                    'type' => 'api',
                    'target' => '/api/v1/dashboard/report?period=' . $range['period'],
                ],
            ],
            'recent_properties' => $this->recentProperties($period, $limit),
            'recent_activity' => $this->recentActivity($limit),
            'performance' => $this->performance($period),
            'quick_links' => $menus,
        ];
    }

    public function recentProperties(string $period = 'month', int $limit = 10): array
    {
        $range = $this->resolvePeriod($period);

        $baseQuery = Property::query()
            ->with([
                'address',
                'images',
                'houseDetails',
                'houseDetails.builtAreaUnit',
                'landUnit',
                'listingType',
                'propertyStatus',
            ])
            ->latest('created_at');

        $items = (clone $baseQuery)
            ->whereBetween('created_at', [$range['start'], $range['end']])
            ->limit($limit)
            ->get();

        $usedFallback = false;

        if ($items->isEmpty()) {
            $items = $baseQuery->limit($limit)->get();
            $usedFallback = true;
        }

        return [
            'period' => $range['period'],
            'used_fallback' => $usedFallback,
            'view_all_url' => '/admin/property',
            'items' => $items
                ->map(fn (Property $property) => $this->transformRecentProperty($property))
                ->values()
                ->all(),
        ];
    }

    public function recentActivity(int $limit = 10): array
    {
        $activities = collect();

        $activities = $activities->concat(
            Inquery::query()
                ->with(['property:id,title'])
                ->latest('created_at')
                ->limit($limit)
                ->get()
                ->map(function (Inquery $inquiry) {
                    $timestamp = $inquiry->created_at ?? now();

                    return [
                        'type' => 'inquiry',
                        'title' => 'New inquiry for ' . ($inquiry->property?->title ?? 'Property'),
                        'description' => trim(($inquiry->name ?? 'Lead') . ' ' . ($inquiry->phone ? '• ' . $inquiry->phone : '')),
                        'time_ago' => $timestamp->diffForHumans(),
                        'timestamp' => $timestamp->toIso8601String(),
                        'target' => '/admin/propertyInquery',
                        'sort_at' => $timestamp->timestamp,
                    ];
                })
        );

        $activities = $activities->concat(
            FieldVisits::query()
                ->with(['property:id,title'])
                ->latest('created_at')
                ->limit($limit)
                ->get()
                ->map(function (FieldVisits $visit) {
                    $timestamp = $visit->created_at ?? now();

                    return [
                        'type' => 'showing',
                        'title' => 'Showing scheduled for ' . ($visit->property?->title ?? 'Property'),
                        'description' => trim(($visit->name ?? 'Visitor') . ' • ' . trim(($visit->date ?? '') . ' ' . ($visit->time ?? ''))),
                        'time_ago' => $timestamp->diffForHumans(),
                        'timestamp' => $timestamp->toIso8601String(),
                        'target' => '/admin/fieldVisit',
                        'sort_at' => $timestamp->timestamp,
                    ];
                })
        );

        $activities = $activities->concat(
            Property::query()
                ->with('address')
                ->latest('created_at')
                ->limit($limit)
                ->get()
                ->map(function (Property $property) {
                    $timestamp = $property->created_at ?? now();

                    return [
                        'type' => 'property',
                        'title' => 'Property added: ' . $property->title,
                        'description' => $property->address?->full_address,
                        'time_ago' => $timestamp->diffForHumans(),
                        'timestamp' => $timestamp->toIso8601String(),
                        'target' => '/admin/property/' . $property->id,
                        'sort_at' => $timestamp->timestamp,
                    ];
                })
        );

        return [
            'items' => $activities
                ->sortByDesc('sort_at')
                ->take($limit)
                ->values()
                ->map(fn (array $activity) => Arr::except($activity, ['sort_at']))
                ->all(),
        ];
    }

    public function performance(string $period = 'month'): array
    {
        $range = $this->resolvePeriod($period);
        $totalInquiries = Inquery::count();
        $respondedInquiries = Inquery::has('inquiryFollowup')->count();
        $convertedInquiries = Inquery::whereHas('latestFollowup.followupStatus', function ($query) {
            $query->whereIn('slug', ['interested', 'visit_scheduled']);
        })->count();

        $viewsCurrentPeriod = (int) Property::whereBetween('updated_at', [$range['start'], $range['end']])->sum('views_count');
        $estimatedRevenueYtd = (float) Property::query()
            ->whereHas('propertyStatus', function ($query) {
                $query->whereIn('slug', ['sold', 'leased']);
            })
            ->whereYear('updated_at', now()->year)
            ->sum('advertise_price');

        return [
            'response_rate' => [
                'label' => 'Response Rate',
                'value' => round($this->ratioToPercentage($respondedInquiries, $totalInquiries), 1),
                'formatted_value' => round($this->ratioToPercentage($respondedInquiries, $totalInquiries), 1) . '%',
            ],
            'listings_viewed' => [
                'label' => 'Listings Viewed',
                'value' => $viewsCurrentPeriod,
                'formatted_value' => $this->formatNumber($viewsCurrentPeriod),
            ],
            'conversion_rate' => [
                'label' => 'Conversion Rate',
                'value' => round($this->ratioToPercentage($convertedInquiries, $totalInquiries), 1),
                'formatted_value' => round($this->ratioToPercentage($convertedInquiries, $totalInquiries), 1) . '%',
            ],
            'total_revenue_ytd' => [
                'label' => 'Total Revenue (YTD)',
                'value' => round($estimatedRevenueYtd, 2),
                'formatted_value' => 'NPR ' . number_format($estimatedRevenueYtd, 0),
                'is_estimated' => true,
                'calculation_basis' => 'Sum of advertise_price for sold or leased properties updated this year.',
            ],
        ];
    }

    public function report(User $user, string $period = 'month', int $limit = 10): array
    {
        return [
            'report_meta' => [
                'generated_at' => now()->toIso8601String(),
                'period' => $period,
                'format' => 'json',
            ],
            'dashboard' => $this->summary($user, $period, $limit),
        ];
    }

    private function transformRecentProperty(Property $property): array
    {
        $bedrooms = $this->countRoomsByKeyword($property, ['bed']);
        $bathrooms = $this->countRoomsByKeyword($property, ['bath', 'toilet', 'wash']);

        $areaValue = $property->houseDetails?->built_area ?: $property->land_area;
        $areaUnit = $property->houseDetails?->builtAreaUnit?->symbol
            ?? $property->landUnit?->symbol
            ?? null;
        $areaDisplay = $areaValue && $areaUnit ? trim($areaValue . ' ' . $areaUnit) : null;

        $specParts = [];
        if ($bedrooms !== null) {
            $specParts[] = $bedrooms . ' beds';
        }
        if ($bathrooms !== null) {
            $specParts[] = $bathrooms . ' baths';
        }
        if ($areaDisplay !== null) {
            $specParts[] = $areaDisplay;
        }

        $featuredImage = $property->images
            ->sortByDesc(fn ($image) => (int) $image->is_featured)
            ->first();

        return [
            'id' => $property->id,
            'title' => $property->title,
            'subtitle' => $property->address?->full_address,
            'specs' => [
                'bedrooms' => $bedrooms,
                'bathrooms' => $bathrooms,
                'area' => $areaValue,
                'area_unit' => $areaUnit,
                'summary' => implode(' • ', $specParts),
            ],
            'status' => $property->propertyStatus?->label ?? ($property->is_status ? 'Active' : 'Inactive'),
            'listing_type' => $property->listingType?->label,
            'price' => [
                'value' => (float) $property->advertise_price,
                'formatted_value' => $this->formatPropertyPrice($property),
                'currency' => $property->currency,
            ],
            'image_url' => $featuredImage?->image_url,
            'target' => '/admin/property/' . $property->id,
            'created_at' => optional($property->created_at)->toIso8601String(),
        ];
    }

    private function pendingInquiries(): Collection
    {
        return Inquery::query()
            ->with(['property:id,title', 'latestFollowup.followupStatus:id,label,slug'])
            ->get()
            ->filter(fn (Inquery $inquiry) => $this->isPendingInquiry($inquiry))
            ->values();
    }

    private function isPendingInquiry(Inquery $inquiry): bool
    {
        $latestFollowup = $inquiry->latestFollowup;

        if (!$latestFollowup) {
            return true;
        }

        return in_array(
            $latestFollowup->followupStatus?->slug,
            ['interested', 'call_later', 'visit_scheduled'],
            true
        );
    }

    private function isUrgentInquiry(Inquery $inquiry): bool
    {
        if (!$this->isPendingInquiry($inquiry)) {
            return false;
        }

        $latestFollowup = $inquiry->latestFollowup;

        if (!$latestFollowup) {
            return optional($inquiry->created_at)->lte(now()->subDays(2)) ?? false;
        }

        if (empty($latestFollowup->next_followup_date)) {
            return false;
        }

        return Carbon::parse($latestFollowup->next_followup_date)->lte(now()->addDays(3));
    }

    private function countRoomsByKeyword(Property $property, array $keywords): ?int
    {
        $floorDetails = $property->houseDetails?->floor_details;

        if (!is_array($floorDetails)) {
            return null;
        }

        $count = 0;

        foreach ($floorDetails as $floor) {
            $rooms = $floor['room_details'] ?? $floor['rooms'] ?? [];

            foreach ($rooms as $room) {
                $name = strtolower(is_array($room) ? (string) ($room['name'] ?? '') : (string) $room);

                foreach ($keywords as $keyword) {
                    if ($name !== '' && str_contains($name, $keyword)) {
                        $count += (int) (is_array($room) ? ($room['count'] ?? 1) : 1);
                        break;
                    }
                }
            }
        }

        return $count > 0 ? $count : null;
    }

    private function formatPropertyPrice(Property $property): string
    {
        $amount = number_format((float) $property->advertise_price, 0);
        $suffix = in_array($property->listingType?->slug, ['for-rent', 'for-lease'], true) ? '/mo' : '';

        return trim($property->currency . ' ' . $amount . $suffix);
    }

    private function buildTrendStat(
        string $label,
        int|float $value,
        string $formattedValue,
        float $changePercentage,
        string $comparisonLabel
    ): array {
        return [
            'label' => $label,
            'value' => $value,
            'formatted_value' => $formattedValue,
            'change_percentage' => round($changePercentage, 1),
            'trend' => $changePercentage > 0 ? 'up' : ($changePercentage < 0 ? 'down' : 'flat'),
            'comparison_label' => $comparisonLabel,
        ];
    }

    private function calculateChange(int|float $current, int|float $previous): float
    {
        if ((float) $previous <= 0.0) {
            return (float) $current > 0.0 ? 100.0 : 0.0;
        }

        return (($current - $previous) / $previous) * 100;
    }

    private function ratioToPercentage(int|float $numerator, int|float $denominator): float
    {
        if ((float) $denominator <= 0.0) {
            return 0.0;
        }

        return ($numerator / $denominator) * 100;
    }

    private function formatNumber(int|float $value): string
    {
        return number_format((float) $value, (fmod((float) $value, 1.0) === 0.0) ? 0 : 1);
    }

    private function countMenus(array $menus): int
    {
        $count = 0;

        foreach ($menus as $menu) {
            $count++;
            $count += $this->countMenus($menu['children'] ?? []);
        }

        return $count;
    }

    private function resolvePeriod(string $period): array
    {
        $period = in_array($period, ['week', 'month', 'year'], true) ? $period : 'month';
        $now = now();

        switch ($period) {
            case 'week':
                $start = $now->copy()->startOfWeek();
                $previousStart = $start->copy()->subWeek();
                $comparisonLabel = 'from last week';
                $versusLabel = 'vs last week';
                break;
            case 'year':
                $start = $now->copy()->startOfYear();
                $previousStart = $start->copy()->subYear()->startOfYear();
                $comparisonLabel = 'from last year';
                $versusLabel = 'vs last year';
                break;
            case 'month':
            default:
                $start = $now->copy()->startOfMonth();
                $previousStart = $start->copy()->subMonth()->startOfMonth();
                $comparisonLabel = 'from last month';
                $versusLabel = 'vs last month';
                break;
        }

        $end = $now->copy()->endOfDay();
        $previousEnd = $start->copy()->subDay()->endOfDay();

        return [
            'period' => $period,
            'start' => $start,
            'end' => $end,
            'previous_start' => $previousStart,
            'previous_end' => $previousEnd,
            'comparison_label' => $comparisonLabel,
            'versus_label' => $versusLabel,
        ];
    }
}
