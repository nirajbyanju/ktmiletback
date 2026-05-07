<?php

namespace App\Services;

use App\Models\InqueryFollowup;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InqueryFollowupService
{
    public function __construct(
        private readonly AdminNotificationService $adminNotificationService,
    ) {
    }

    public function store(array $data): InqueryFollowup
    {
        return DB::transaction(function () use ($data) {
            if (Auth::id() && empty($data['admin_id'])) {
                $data['admin_id'] = Auth::id();
            }

            $followup = InqueryFollowup::create([
                'inquiry_id' => $data['inquiry_id'],
                'admin_id' => $data['admin_id'] ?? null,
                'contact_method_id' => $data['contact_method_id'] ?? null,
                'followup_status_id' => $data['followup_status_id'] ?? null,
                'message' => $data['message'] ?? null,
                'next_followup_date' => $data['next_followup_date'] ?? null,
            ]);

            $followupId = $followup->id;

            DB::afterCommit(function () use ($followupId): void {
                $freshFollowup = InqueryFollowup::with([
                    'inquiry.property',
                    'admin',
                    'contactMethod',
                    'followupStatus',
                ])->find($followupId);

                if ($freshFollowup) {
                    $this->adminNotificationService->notifyInquiryFollowupCreated(
                        $freshFollowup,
                        $freshFollowup->admin
                    );
                }
            });

            return $followup->load([
                'inquiry.property',
                'admin',
                'contactMethod',
                'followupStatus',
            ]);
        });
    }

    public function listActiveInqueryFollowup($request, $inqueryId)
    {
        $orderBy = in_array(strtoupper($request->get('order_by')), ['ASC', 'DESC'])
            ? strtoupper($request->get('order_by'))
            : 'DESC';

        $orderColumn = $request->get('order_column') ?? 'created_at';
        $orderColumn = Str::snake($orderColumn);

        $limit = $request->get('limit');
        if (empty($limit) || $limit == 0) {
            $limit = $request->header('X-Limit-No') ?? 10;
        }

        $limit = is_numeric($limit) ? (int) $limit : 10;
        $page  = is_numeric($request->get('page')) ? (int) $request->get('page') : 1;

        $allowedFilters = ['category_id', 'vacancy_type', 'is_status', 'title'];

        $filters = collect($request->all())
            ->mapWithKeys(function ($value, $key) {
                return [Str::snake($key) => $value];
            })
            ->only($allowedFilters)
            ->filter();

        $query = InqueryFollowup::with(['inquiry']);

        foreach ($filters as $field => $value) {
            if ($field === 'title') {
                $query->where($field, 'like', "%{$value}%");
            } else {
                $query->where($field, $value);
            }
        }

        $query->where('inquiry_id', $inqueryId);
        $query->orderBy($orderColumn, $orderBy);

        return $query->paginate($limit, ['*'], 'page', $page);
    }
}
