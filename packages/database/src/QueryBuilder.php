<?php

declare(strict_types=1);

namespace Forjix\Database;

use Closure;
use Forjix\Support\Collection;

class QueryBuilder
{
    protected Connection $connection;
    protected ?string $table = null;
    protected array $columns = ['*'];
    protected bool $distinct = false;
    protected array $joins = [];
    protected array $wheres = [];
    protected array $groups = [];
    protected array $havings = [];
    protected array $orders = [];
    protected ?int $limit = null;
    protected ?int $offset = null;
    protected array $bindings = [
        'select' => [],
        'from' => [],
        'join' => [],
        'where' => [],
        'groupBy' => [],
        'having' => [],
        'order' => [],
    ];

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function from(string $table, ?string $as = null): static
    {
        $this->table = $as ? "{$table} as {$as}" : $table;

        return $this;
    }

    public function table(string $table, ?string $as = null): static
    {
        return $this->from($table, $as);
    }

    public function select(string|array ...$columns): static
    {
        $this->columns = [];

        foreach ($columns as $column) {
            if (is_array($column)) {
                $this->columns = array_merge($this->columns, $column);
            } else {
                $this->columns[] = $column;
            }
        }

        return $this;
    }

    public function addSelect(string|array ...$columns): static
    {
        foreach ($columns as $column) {
            if (is_array($column)) {
                $this->columns = array_merge($this->columns, $column);
            } else {
                $this->columns[] = $column;
            }
        }

        return $this;
    }

    public function distinct(): static
    {
        $this->distinct = true;

        return $this;
    }

    public function join(string $table, string $first, string $operator, string $second, string $type = 'inner'): static
    {
        $this->joins[] = compact('type', 'table', 'first', 'operator', 'second');

        return $this;
    }

    public function leftJoin(string $table, string $first, string $operator, string $second): static
    {
        return $this->join($table, $first, $operator, $second, 'left');
    }

    public function rightJoin(string $table, string $first, string $operator, string $second): static
    {
        return $this->join($table, $first, $operator, $second, 'right');
    }

    public function crossJoin(string $table): static
    {
        $this->joins[] = ['type' => 'cross', 'table' => $table];

        return $this;
    }

    public function where(string|Closure $column, mixed $operator = null, mixed $value = null, string $boolean = 'and'): static
    {
        if ($column instanceof Closure) {
            return $this->whereNested($column, $boolean);
        }

        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => $boolean,
        ];

        $this->addBinding($value, 'where');

        return $this;
    }

    public function orWhere(string|Closure $column, mixed $operator = null, mixed $value = null): static
    {
        return $this->where($column, $operator, $value, 'or');
    }

    protected function whereNested(Closure $callback, string $boolean = 'and'): static
    {
        $query = new static($this->connection);
        $query->from($this->table);

        $callback($query);

        if (!empty($query->wheres)) {
            $this->wheres[] = [
                'type' => 'nested',
                'query' => $query,
                'boolean' => $boolean,
            ];

            $this->addBinding($query->getBindings()['where'], 'where');
        }

        return $this;
    }

    public function whereIn(string $column, array $values, string $boolean = 'and', bool $not = false): static
    {
        $type = $not ? 'not in' : 'in';

        $this->wheres[] = [
            'type' => $type,
            'column' => $column,
            'values' => $values,
            'boolean' => $boolean,
        ];

        $this->addBinding($values, 'where');

        return $this;
    }

    public function whereNotIn(string $column, array $values, string $boolean = 'and'): static
    {
        return $this->whereIn($column, $values, $boolean, true);
    }

    public function orWhereIn(string $column, array $values): static
    {
        return $this->whereIn($column, $values, 'or');
    }

    public function orWhereNotIn(string $column, array $values): static
    {
        return $this->whereIn($column, $values, 'or', true);
    }

    public function whereNull(string $column, string $boolean = 'and', bool $not = false): static
    {
        $type = $not ? 'not null' : 'null';

        $this->wheres[] = [
            'type' => $type,
            'column' => $column,
            'boolean' => $boolean,
        ];

        return $this;
    }

    public function whereNotNull(string $column, string $boolean = 'and'): static
    {
        return $this->whereNull($column, $boolean, true);
    }

    public function orWhereNull(string $column): static
    {
        return $this->whereNull($column, 'or');
    }

    public function orWhereNotNull(string $column): static
    {
        return $this->whereNull($column, 'or', true);
    }

    public function whereBetween(string $column, array $values, string $boolean = 'and', bool $not = false): static
    {
        $type = $not ? 'not between' : 'between';

        $this->wheres[] = [
            'type' => $type,
            'column' => $column,
            'values' => $values,
            'boolean' => $boolean,
        ];

        $this->addBinding($values, 'where');

        return $this;
    }

    public function whereNotBetween(string $column, array $values, string $boolean = 'and'): static
    {
        return $this->whereBetween($column, $values, $boolean, true);
    }

    public function whereLike(string $column, string $value, string $boolean = 'and'): static
    {
        return $this->where($column, 'like', $value, $boolean);
    }

    public function whereRaw(string $sql, array $bindings = [], string $boolean = 'and'): static
    {
        $this->wheres[] = [
            'type' => 'raw',
            'sql' => $sql,
            'boolean' => $boolean,
        ];

        $this->addBinding($bindings, 'where');

        return $this;
    }

    public function groupBy(string ...$groups): static
    {
        foreach ($groups as $group) {
            $this->groups[] = $group;
        }

        return $this;
    }

    public function having(string $column, string $operator, mixed $value, string $boolean = 'and'): static
    {
        $this->havings[] = [
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => $boolean,
        ];

        $this->addBinding($value, 'having');

        return $this;
    }

    public function orHaving(string $column, string $operator, mixed $value): static
    {
        return $this->having($column, $operator, $value, 'or');
    }

    public function orderBy(string $column, string $direction = 'asc'): static
    {
        $this->orders[] = [
            'column' => $column,
            'direction' => strtolower($direction) === 'desc' ? 'desc' : 'asc',
        ];

        return $this;
    }

    public function orderByDesc(string $column): static
    {
        return $this->orderBy($column, 'desc');
    }

    public function latest(string $column = 'created_at'): static
    {
        return $this->orderBy($column, 'desc');
    }

    public function oldest(string $column = 'created_at'): static
    {
        return $this->orderBy($column, 'asc');
    }

    public function inRandomOrder(): static
    {
        $this->orders[] = ['type' => 'raw', 'sql' => 'RAND()'];

        return $this;
    }

    public function limit(int $value): static
    {
        $this->limit = max(0, $value);

        return $this;
    }

    public function take(int $value): static
    {
        return $this->limit($value);
    }

    public function offset(int $value): static
    {
        $this->offset = max(0, $value);

        return $this;
    }

    public function skip(int $value): static
    {
        return $this->offset($value);
    }

    public function forPage(int $page, int $perPage = 15): static
    {
        return $this->offset(($page - 1) * $perPage)->limit($perPage);
    }

    public function get(array|string $columns = ['*']): Collection
    {
        if ($columns !== ['*']) {
            $this->select(...(array) $columns);
        }

        $results = $this->connection->select(
            $this->toSql(),
            $this->getBindingsFlat()
        );

        return new Collection($results);
    }

    public function first(array|string $columns = ['*']): ?object
    {
        return $this->limit(1)->get($columns)->first();
    }

    public function find(int|string $id, array|string $columns = ['*']): ?object
    {
        return $this->where('id', $id)->first($columns);
    }

    public function value(string $column): mixed
    {
        $result = $this->first([$column]);

        return $result ? $result->{$column} : null;
    }

    public function pluck(string $column, ?string $key = null): Collection
    {
        $results = $this->get($key ? [$column, $key] : [$column]);

        if ($key) {
            return $results->keyBy($key)->map(fn($item) => $item->{$column});
        }

        return $results->map(fn($item) => $item->{$column});
    }

    public function count(string $column = '*'): int
    {
        return (int) $this->aggregate('count', $column);
    }

    public function max(string $column): mixed
    {
        return $this->aggregate('max', $column);
    }

    public function min(string $column): mixed
    {
        return $this->aggregate('min', $column);
    }

    public function avg(string $column): mixed
    {
        return $this->aggregate('avg', $column);
    }

    public function sum(string $column): mixed
    {
        return $this->aggregate('sum', $column) ?? 0;
    }

    protected function aggregate(string $function, string $column): mixed
    {
        $this->columns = ["{$function}({$column}) as aggregate"];

        $result = $this->connection->selectOne(
            $this->toSql(),
            $this->getBindingsFlat()
        );

        return $result?->aggregate;
    }

    public function exists(): bool
    {
        return $this->count() > 0;
    }

    public function doesntExist(): bool
    {
        return !$this->exists();
    }

    public function insert(array $values): bool
    {
        if (empty($values)) {
            return true;
        }

        // Handle single insert vs batch insert
        if (!is_array(reset($values))) {
            $values = [$values];
        }

        $columns = array_keys(reset($values));
        $parameters = [];
        $bindings = [];

        foreach ($values as $record) {
            $placeholders = [];
            foreach ($columns as $column) {
                $placeholders[] = '?';
                $bindings[] = $record[$column] ?? null;
            }
            $parameters[] = '(' . implode(', ', $placeholders) . ')';
        }

        $sql = sprintf(
            'insert into %s (%s) values %s',
            $this->connection->prefixTable($this->table),
            implode(', ', $columns),
            implode(', ', $parameters)
        );

        return $this->connection->insert($sql, $bindings);
    }

    public function insertGetId(array $values, string $sequence = 'id'): int|string
    {
        $this->insert($values);

        return $this->connection->lastInsertId($sequence);
    }

    public function update(array $values): int
    {
        $columns = [];
        $bindings = [];

        foreach ($values as $column => $value) {
            $columns[] = "{$column} = ?";
            $bindings[] = $value;
        }

        $sql = sprintf(
            'update %s set %s%s',
            $this->connection->prefixTable($this->table),
            implode(', ', $columns),
            $this->compileWheres()
        );

        return $this->connection->update($sql, array_merge($bindings, $this->bindings['where']));
    }

    public function increment(string $column, int|float $amount = 1, array $extra = []): int
    {
        $wrapped = "{$column} = {$column} + {$amount}";

        $columns = [$wrapped];
        $bindings = [];

        foreach ($extra as $col => $value) {
            $columns[] = "{$col} = ?";
            $bindings[] = $value;
        }

        $sql = sprintf(
            'update %s set %s%s',
            $this->connection->prefixTable($this->table),
            implode(', ', $columns),
            $this->compileWheres()
        );

        return $this->connection->update($sql, array_merge($bindings, $this->bindings['where']));
    }

    public function decrement(string $column, int|float $amount = 1, array $extra = []): int
    {
        return $this->increment($column, $amount * -1, $extra);
    }

    public function delete(?int $id = null): int
    {
        if ($id !== null) {
            $this->where('id', $id);
        }

        $sql = sprintf(
            'delete from %s%s',
            $this->connection->prefixTable($this->table),
            $this->compileWheres()
        );

        return $this->connection->delete($sql, $this->bindings['where']);
    }

    public function truncate(): void
    {
        $sql = sprintf('truncate table %s', $this->connection->prefixTable($this->table));

        $this->connection->statement($sql);
    }

    public function toSql(): string
    {
        $sql = $this->compileSelect();
        $sql .= $this->compileFrom();
        $sql .= $this->compileJoins();
        $sql .= $this->compileWheres();
        $sql .= $this->compileGroups();
        $sql .= $this->compileHavings();
        $sql .= $this->compileOrders();
        $sql .= $this->compileLimit();
        $sql .= $this->compileOffset();

        return $sql;
    }

    protected function compileSelect(): string
    {
        $distinct = $this->distinct ? 'distinct ' : '';

        return 'select ' . $distinct . implode(', ', $this->columns);
    }

    protected function compileFrom(): string
    {
        return ' from ' . $this->connection->prefixTable($this->table);
    }

    protected function compileJoins(): string
    {
        if (empty($this->joins)) {
            return '';
        }

        $sql = [];

        foreach ($this->joins as $join) {
            if ($join['type'] === 'cross') {
                $sql[] = "cross join {$join['table']}";
            } else {
                $sql[] = "{$join['type']} join {$join['table']} on {$join['first']} {$join['operator']} {$join['second']}";
            }
        }

        return ' ' . implode(' ', $sql);
    }

    protected function compileWheres(): string
    {
        if (empty($this->wheres)) {
            return '';
        }

        $sql = [];

        foreach ($this->wheres as $i => $where) {
            $boolean = $i === 0 ? '' : " {$where['boolean']} ";

            $sql[] = match ($where['type']) {
                'basic' => $boolean . "{$where['column']} {$where['operator']} ?",
                'in' => $boolean . "{$where['column']} in (" . implode(', ', array_fill(0, count($where['values']), '?')) . ')',
                'not in' => $boolean . "{$where['column']} not in (" . implode(', ', array_fill(0, count($where['values']), '?')) . ')',
                'null' => $boolean . "{$where['column']} is null",
                'not null' => $boolean . "{$where['column']} is not null",
                'between' => $boolean . "{$where['column']} between ? and ?",
                'not between' => $boolean . "{$where['column']} not between ? and ?",
                'nested' => $boolean . '(' . ltrim($where['query']->compileWheres(), ' where ') . ')',
                'raw' => $boolean . $where['sql'],
                default => '',
            };
        }

        return ' where ' . implode('', $sql);
    }

    protected function compileGroups(): string
    {
        if (empty($this->groups)) {
            return '';
        }

        return ' group by ' . implode(', ', $this->groups);
    }

    protected function compileHavings(): string
    {
        if (empty($this->havings)) {
            return '';
        }

        $sql = [];

        foreach ($this->havings as $i => $having) {
            $boolean = $i === 0 ? '' : " {$having['boolean']} ";
            $sql[] = $boolean . "{$having['column']} {$having['operator']} ?";
        }

        return ' having ' . implode('', $sql);
    }

    protected function compileOrders(): string
    {
        if (empty($this->orders)) {
            return '';
        }

        $sql = [];

        foreach ($this->orders as $order) {
            if (isset($order['type']) && $order['type'] === 'raw') {
                $sql[] = $order['sql'];
            } else {
                $sql[] = "{$order['column']} {$order['direction']}";
            }
        }

        return ' order by ' . implode(', ', $sql);
    }

    protected function compileLimit(): string
    {
        if ($this->limit === null) {
            return '';
        }

        return " limit {$this->limit}";
    }

    protected function compileOffset(): string
    {
        if ($this->offset === null) {
            return '';
        }

        return " offset {$this->offset}";
    }

    protected function addBinding(mixed $value, string $type = 'where'): static
    {
        if (is_array($value)) {
            $this->bindings[$type] = array_merge($this->bindings[$type], $value);
        } else {
            $this->bindings[$type][] = $value;
        }

        return $this;
    }

    public function getBindings(): array
    {
        return $this->bindings;
    }

    public function getBindingsFlat(): array
    {
        return array_merge(...array_values($this->bindings));
    }

    public function newQuery(): static
    {
        return new static($this->connection);
    }

    public function clone(): static
    {
        return clone $this;
    }
}
