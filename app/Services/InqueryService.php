<?php
// app/Services/Property/PropertyStoreService.php

namespace App\Services;

use App\Models\Inquery;
use Illuminate\Support\Str;

class InqueryService
{
    /**
     * Store a new property with all relationships
     *
     * @param array $data
     * @return Inquery
     */

        public function listActiveInquery($request)
    {

        $orderBy = in_array(strtoupper($request->get('order_by')), ['ASC', 'DESC'])
            ? strtoupper($request->get('order_by'))
            : 'DESC';

        $allowedSortColumns = [
            'created_at',
            'updated_at',
            'name',
            'email',
            'phone',
            'inquiry_type_id',
            'property_type_id',
            'property_id',
            'status',
        ];

        $requestedOrderColumn = Str::snake($request->get('order_column') ?? 'created_at');
        $orderColumn = in_array($requestedOrderColumn, $allowedSortColumns, true)
            ? $requestedOrderColumn
            : 'created_at';

        $limit = $request->get('limit');
        if (empty($limit) || $limit == 0) {
            $limit = $request->header('X-Limit-No') ?? 10;
        }

        $limit = is_numeric($limit) ? (int) $limit : 10;
        $page  = is_numeric($request->get('page')) ? (int) $request->get('page') : 1;

        $allowedFilters = [
            'property_id',
            'inquiry_type_id',
            'property_type_id',
            'name',
            'email',
            'phone',
            'from',
            'status',
        ];

        $filters = collect($request->all())
            ->mapWithKeys(function ($value, $key) {
                return [Str::snake($key) => $value];
            })
            ->only($allowedFilters)
            ->filter();

        $query = Inquery::with(['property']);

        foreach ($filters as $field => $value) {
            if (in_array($field, ['name', 'email', 'phone'], true)) {
                $query->where($field, 'like', "%{$value}%");
            } else {
                $query->where($field, $value);
            }
        }

        $query->orderBy($orderColumn, $orderBy);

        return $query->paginate($limit, ['*'], 'page', $page);
    }
}
