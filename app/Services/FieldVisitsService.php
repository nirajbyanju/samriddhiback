<?php
// app/Services/Property/FieldVisitsService.php

namespace App\Services; // Changed namespace to be more specific

use App\Models\FieldVisits;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class FieldVisitsService
{
    /**
     * List active field visits with filtering and pagination
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function listActiveFieldVisits($request)
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
        $allowedFilters = ['property_id', 'status', 'name', 'email', 'phone']; // Updated for field visits

        // Convert request inputs to snake_case
        $filters = collect($request->all())
            ->mapWithKeys(function ($value, $key) {
                return [Str::snake($key) => $value];
            })
            ->only($allowedFilters)
            ->filter(); // remove empty values

        // Base Query
        $query = FieldVisits::with(['property']);

        // Apply Filters
        foreach ($filters as $field => $value) {
            if (in_array($field, ['name', 'email', 'message'])) {
                $query->where($field, 'like', "%{$value}%");
            } else {
                $query->where($field, $value);
            }
        }

        // Apply Ordering
        $query->orderBy($orderColumn, $orderBy);

        return $query->paginate($limit, ['*'], 'page', $page);
    }

    /**
     * Store a new field visit
     *
     * @param array $data
     * @return FieldVisits
     */
    public function store(array $data)
    {
        $validator = Validator::make($data, [
            'property_id' => 'required|exists:properties,id',
            'date' => 'required|date',
            'time' => 'required',
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'message' => 'nullable|string',
            'remarks' => 'nullable|string',
            'accept_term' => 'required|boolean',
            'status' => 'sometimes|in:pending,confirmed,cancelled,completed'
        ]);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        return FieldVisits::create($data);
    }
}