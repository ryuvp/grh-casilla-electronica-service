<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait Filterable
{
    //protected array $filters = [];

    public function scopeFilter(Builder $builder, Request $request, array $filters = []): Builder
    {
        if (!empty($filters)) {
            $this->addFilters($filters);
        }

        // Select columns
        if ($request->filled('select')) {
            $builder->select($request->input('select'));
        }

        // With relationships
        if ($request->filled('with')) {
            $builder->with($request->input('with'));
        }

        // Apply filters
        $builder->where(function ($query) use ($request) {
            foreach ($request->only($this->filters) as $filter => $value) {
                $this->resolveFilter($filter, $query, $value);
            }
        });

        // Pagination (skip & take)
        if ($request->filled('skip')) {
            $builder->skip((int) $request->input('skip'));
        }

        if ($request->filled('take')) {
            $builder->take((int) $request->input('take'));
        }

        // Order by (format: ["column.direction", ...])
        if ($request->filled('orders')) {
            foreach ((array) $request->input('orders') as $order) {
                [$column, $direction] = explode('.', $order) + [null, 'asc'];
                if ($column && in_array(strtolower($direction), ['asc', 'desc'])) {
                    $builder->orderBy($column, $direction);
                }
            }
        }

        return $builder;
    }

    public function addFilters(array $filters): self
    {
        $this->filters = array_merge($this->filters, $filters);
        return $this;
    }

    public function addFilter(string $filter): self
    {
        $this->filters[] = $filter;
        return $this;
    }

    protected function resolveFilter(string $filter, Builder $builder, mixed $value): Builder
    {
        if (is_object($value)) {
            // Soporte para filtros anidados u objetos con su propio método filter()
            return (new $value)->filter($builder, $value);
        }

        if (!is_array($value)) {
            // Filtro simple: columna = valor
            return $builder->where($filter, $value);
        }

        // Filtros con operadores
        $operator = strtoupper($value[0]);
        $operand = $value[1] ?? null;

        return match ($operator) {
            'IN'   => $builder->whereIn($filter, array_slice($value, 1)),
            'LIKE' => $builder->where($filter, 'LIKE', "%$operand%"),
            default => count($value) === 2
                ? $builder->where($filter, $value[0], $value[1])
                : $builder,
        };
    }
}
