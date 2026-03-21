<?php
// app/Services/Property/PropertyStoreService.php

namespace App\Services;

use App\Models\InqueryFollowup;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class InqueryFollowupService
{
    /**
     * Store a new property with all relationships
     *
     * @param array $data
     * @return InqueryFollowup
     */

        public function listActiveInqueryFollowup($request, $inqueryId)
    {

        // Sorting
        $orderBy = in_array(strtoupper($request->get('order_by')), ['ASC', 'DESC'])
            ? strtoupper($request->get('order_by'))
            : 'DESC';

        $orderColumn = $request->get('order_column') ?? 'created_at';
        $orderColumn = Str::snake($orderColumn);

        // Pagination
        $limit = $request->get('limit');
        if (empty($limit) || $limit == 0) {
            $limit = $request->header('X-Limit-No') ?? 10;
        }

        $limit = is_numeric($limit) ? (int) $limit : 10;
        $page  = is_numeric($request->get('page')) ? (int) $request->get('page') : 1;

        // Allowed DB filters (snake_case)
        $allowedFilters = ['category_id', 'vacancy_type', 'is_status', 'title'];

        // Convert request inputs to snake_case
        $filters = collect($request->all())
            ->mapWithKeys(function ($value, $key) {
                return [Str::snake($key) => $value];
            })
            ->only($allowedFilters)
            ->filter(); // remove empty values

        // Base Query
        $query = InqueryFollowup::with(['inquiry']);

        // Apply Filters
        foreach ($filters as $field => $value) {
            if ($field === 'title') {
                $query->where($field, 'like', "%{$value}%");
            } else {
                $query->where($field, $value);
            }

        }

        $query->where('inquiry_id', $inqueryId);

        // Apply Ordering
        $query->orderBy($orderColumn, $orderBy);

        return $query->paginate($limit, ['*'], 'page', $page);
    }
}   