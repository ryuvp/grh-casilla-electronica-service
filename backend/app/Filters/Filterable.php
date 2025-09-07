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

        // Filtro especial: búsqueda por múltiples columnas
        if ($request->filled('search') && $request->filled('searchField')) {
            $search = $request->input('search');
            $fields = (array) $request->input('searchField');
            $table = $builder->getModel()->getTable();

            $builder->where(function ($query) use ($fields, $search, $table) {
                foreach ($fields as $field) {
                    if (is_array($search)) {
                        // 👇 IN si search es array
                        $query->orWhereIn("$table.$field", $search);
                    } else {
                        // 👇 Búsqueda por ILIKE si es string
                        $query->orWhere("$table.$field", 'ILIKE', "%$search%");
                    }
                }
            });
        }

        // Filtro de rango de fechas
        if ($request->filled('dateField')) {
            $field = $request->input('dateField');

            if ($request->filled('dateStart')) {
                $builder->whereDate($field, '>=', $request->input('dateStart'));
            }

            if ($request->filled('dateEnd')) {
                $builder->whereDate($field, '<=', $request->input('dateEnd'));
            }
        }

        // Filtros simples definidos en el modelo (por exactitud o con operadores)
        if (!empty($this->filters)) {
            $builder->where(function ($query) use ($request) {
                foreach ($request->only($this->filters) as $filter => $value) {
                    $this->resolveFilter($filter, $query, $value);
                }
            });
        }

        // Paginación opcional sin paginate()
        if ($request->filled('skip')) {
            $builder->skip((int) $request->input('skip'));
        }

        if ($request->filled('take')) {
            $builder->take((int) $request->input('take'));
        }

        // Orden dinámico
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
        $this->filters = array_merge($this->filters ?? [], $filters);
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
            return (new $value)->filter($builder, $value);
        }

        if (!is_array($value)) {
            return $builder->where($filter, $value);
        }

        $operator = strtoupper($value[0]);
        $operand = $value[1] ?? null;

        return match ($operator) {
            'IN'   => $builder->whereIn($filter, array_slice($value, 1)),
            'LIKE' => $builder->where($filter, 'LIKE', "%$operand%"),
            'ILIKE' => $builder->where($filter, 'ILIKE', "%$operand%"),
            default => count($value) === 2
                ? $builder->where($filter, $value[0], $value[1])
                : $builder,
        };
    }
}
