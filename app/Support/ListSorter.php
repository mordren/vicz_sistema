<?php

namespace App\Support;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class ListSorter
{
    /**
     * Applies the ?sort=&direction= ordering from the request, restricted to the given whitelist.
     *
     * @param  array<string, string|Closure(Builder, string): mixed>  $columns  sort key => column name or closure
     * @return array{sort: string|null, direction: string|null}
     */
    public static function apply(
        Builder $query,
        Request $request,
        array $columns,
        ?string $defaultSort = null,
        string $defaultDirection = 'asc',
    ): array {
        $requested = $request->query('sort');

        if (is_string($requested) && array_key_exists($requested, $columns)) {
            $sort = $requested;
            $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';
        } elseif ($defaultSort !== null && array_key_exists($defaultSort, $columns)) {
            $sort = $defaultSort;
            $direction = $defaultDirection === 'desc' ? 'desc' : 'asc';
        } else {
            return ['sort' => null, 'direction' => null];
        }

        $column = $columns[$sort];

        if ($column instanceof Closure) {
            $column($query, $direction);
        } else {
            $query->orderByRaw($query->getQuery()->getGrammar()->wrap($column).' IS NULL')
                ->orderBy($column, $direction);
        }

        $query->orderBy($query->getModel()->getQualifiedKeyName(), $direction);

        return ['sort' => $sort, 'direction' => $direction];
    }

    /**
     * Orders by a fixed priority of enum values (e.g. OVERDUE before OPEN) instead of alphabetically.
     *
     * @param  list<string>  $priority
     */
    public static function byPriority(string $column, array $priority): Closure
    {
        return static function (Builder $query, string $direction) use ($column, $priority): void {
            $cases = '';
            foreach (array_values($priority) as $i => $_) {
                $cases .= ' WHEN ? THEN '.$i;
            }

            $query->orderByRaw(
                'CASE '.$query->getQuery()->getGrammar()->wrap($column).$cases.' ELSE '.count($priority).' END '.$direction,
                array_values($priority),
            );
        };
    }
}
