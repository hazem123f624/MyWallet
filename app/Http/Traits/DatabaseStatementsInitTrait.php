<?php

namespace App\Http\Traits;

trait DatabaseStatementsInitTrait
{
    /**
     * Apply filters and conditions to the query.
     */
    public function StatementDB($query, array $request, bool $queryBuilder = true, bool $count = false)
    {
        $conditionsMap = [
            'where' => fn ($q, $k, $v) => $q->where($k, $v),
            'whereNotEqual' => fn ($q, $k, $v) => $q->where($k, '<>', $v),
            'orWhere' => fn ($q, $k, $v) => $q->orWhere($k, $v),
            'whereLike' => fn ($q, $k, $v) => $q->where($k, 'like', "%{$v}%"),
            'whereIn' => fn ($q, $k, $v) => $q->whereIn($k, $v),
            'whereNotIn' => fn ($q, $k, $v) => $q->whereNotIn($k, $v),
        ];

        foreach ($conditionsMap as $key => $callback) {
            if (! $this->has($request, $key)) {
                continue;
            }

            foreach ($request[$key] as $item) {
                foreach ($item as $column => $value) {
                    $query = $callback($query, $column, $value);
                }
            }
        }
        if ($this->has($request, 'whereNull')) {
            foreach ($request['whereNull'] as $col) {
                $query = $query->whereNull($col);
            }
        }

        if ($this->has($request, 'whereNotNull')) {
            foreach ($request['whereNotNull'] as $col) {
                $query = $query->whereNotNull($col);
            }
        }
        if ($this->has($request, 'whereHas')) {
            foreach ($request['whereHas'] as $relation => $conditions) {
                $query = $query->whereHas($relation, fn ($q) => $q->where($conditions));
            }
        }
        if ($this->has($request, 'whereHasClosure')) {
            foreach ($request['whereHasClosure'] as $relation => $closure) {
                $query = $query->whereHas($relation, $closure);
            }
        }
        if ($this->has($request, 'whereDate', false)) {
            foreach ($request['whereDate'] as $arr) {
                $query = $query->whereDate($arr[0], $arr[1], $arr[2]);
            }
        }
        if ($this->has($request, 'whereInJoin')) {
            foreach ($request['whereInJoin'] as $join) {
                $query = $query->join($join[1], function ($q) use ($join) {
                    $q->on($join[1].'.'.$join[2], $join[0].'.id');

                    if (count($join) === 6) {
                        $q->whereIn($join[3], $join[5])
                            ->orWhereIn($join[4], $join[5]);
                    } else {
                        $q->whereIn($join[3], $join[4])
                            ->whereNull('date_to');
                    }
                });
            }
        }
        if ($this->has($request, 'with')) {
            $query = $query->with($request['with']);
        }
        if ($this->has($request, 'withCount')) {
            $query = $query->withCount($request['withCount']);
        }
        if ($this->has($request, 'withSum')) {
            if (is_array($request['withSum'])) {
                foreach ($request['withSum'] as $relation => $column) {
                    if (is_int($relation)) {
                        if (is_array($column) && count($column) === 2) {
                            $query = $query->withSum($column[0], $column[1]);
                        } else {
                            $query = $query->withSum($column);
                        }
                    } else {
                        $query = $query->withSum($relation, $column);
                    }
                }
            } else {
                $query = $query->withSum($request['withSum']);
            }
        }
        if ($this->has($request, 'withTrashed')) {
            $query = $query->withTrashed();
        }
        if ($this->has($request, 'onlyTrashed')) {
            $query = $query->onlyTrashed();
        }

        if ($this->has($request, 'orderBy')) {
            foreach ($request['orderBy'] as $column => $direction) {
                $query = $query->orderBy($column, $direction);
            }
        }
        if ($queryBuilder) {
            if ($this->has($request, 'select')) {
                $request['select'][] = 'id';
                $query = $query->select($request['select']);
            }

            if ($this->has($request, 'per_page', false)) {
                $query = $query->limit($request['per_page']);
            }
        }
        if ($this->has($request, 'offset', false)) {
            $query = $query->offset($request['offset']);
        }
        return $count ? $query->count() : $query;
    }
    public function SetStatementDB(array $request, array $filters, array $exceptLike = [], array $dates = [], array $orders = []): array
    {
        unset($filters['page'],$filters['main_id']);
        foreach ($dates as $key => $value) {
            if ($this->has($filters, $key)) {
                if (! $this->has($request, 'created_from', false)) {
                    $request['whereDate'][] = [$value['field'], $value['operator'], $filters[$key]];
                    unset($filters[$key]);
                }
            }
        }

        $request['row_in_page'] = 10;
        if (request()->has('per_page') || $this->has($filters, 'per_page', false)) {
            $request['row_in_page'] = request()->per_page ?? $filters['per_page'];
            unset($filters['row_in_page']);
            unset($filters['per_page']);
        }

        if ($this->has($filters, 'trashed', false)) {
            $request['withTrashed'] = true;
            unset($filters['trashed']);
        }
        if ($this->has($filters, 'with_trashed', false)) {
            $request['withTrashed'] = true;
            unset($filters['with_trashed']);
        }
        if ($this->has($filters, 'only_trashed', false)) {
            $request['onlyTrashed'] = true;
            unset($filters['only_trashed']);
        }
        if ($this->has($filters, 'search', false)) {
            $request['search'] = $filters['search'];
            unset($filters['search']);
        }

        /** --------------------------------------------------------
         *  Remaining filters → Build SQL clauses
         * ------------------------------------------------------- */
        foreach ($filters as $key => $value) {
            if ($value === '' || $value === null) {
                continue; // skip empty values
            }
            // Numeric OR key is allowed as normal where
            if (is_numeric($value) || in_array($key, $exceptLike, true)) {
                $request['where'][] = [$key => $value];
                continue;
            }
            // Arrays → could be JOIN or whereIn
            if (is_array($value)) {
                // Format: table+relation+column
                $joinParts = explode('+', $key);
                if (count($joinParts) > 1) {
                    // JOIN IN
                    $joinParts[] = $value;
                    $request['whereInJoin'][] = $joinParts;
                } else {
                    // Normal whereIn
                    $request['whereIn'][] = [$key => $value];
                }
                continue;
            }
            // Default: LIKE search
            $request['whereLike'][] = [$key => $value];
        }
        foreach ($orders as $key => $value) {
            $request['orderBy'][$key] = $value;
        }

        return $request;
    }

    //    public function
    private function has(array $array, string $key, bool $checkNotEmpty = true): bool
    {
        if (! array_key_exists($key, $array)) {
            return false;
        }
        if ($checkNotEmpty === false) {
            return $array[$key] !== '' && $array[$key] !== null;
        }

        return ! empty($array[$key]);
    }
}
