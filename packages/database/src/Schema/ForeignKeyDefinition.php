<?php

declare(strict_types=1);

namespace Forjix\Database\Schema;

use Forjix\Support\Fluent;

class ForeignKeyDefinition extends Fluent
{
    public function references(string $column): static
    {
        $this->set('references', $column);

        return $this;
    }

    public function on(string $table): static
    {
        $this->set('on', $table);

        return $this;
    }

    public function onDelete(string $action): static
    {
        $this->set('onDelete', strtoupper($action));

        return $this;
    }

    public function onUpdate(string $action): static
    {
        $this->set('onUpdate', strtoupper($action));

        return $this;
    }

    public function cascadeOnDelete(): static
    {
        return $this->onDelete('cascade');
    }

    public function cascadeOnUpdate(): static
    {
        return $this->onUpdate('cascade');
    }

    public function restrictOnDelete(): static
    {
        return $this->onDelete('restrict');
    }

    public function restrictOnUpdate(): static
    {
        return $this->onUpdate('restrict');
    }

    public function nullOnDelete(): static
    {
        return $this->onDelete('set null');
    }

    public function constrained(?string $table = null, string $column = 'id'): static
    {
        if ($table === null) {
            $table = str_replace('_id', '', $this->get('column')) . 's';
        }

        return $this->references($column)->on($table);
    }

    public function toSql(string $table): string
    {
        $sql = sprintf(
            'alter table %s add constraint %s foreign key (%s) references %s (%s)',
            $table,
            $this->getConstraintName($table),
            $this->get('column'),
            $this->get('on'),
            $this->get('references')
        );

        if ($onDelete = $this->get('onDelete')) {
            $sql .= " on delete {$onDelete}";
        }

        if ($onUpdate = $this->get('onUpdate')) {
            $sql .= " on update {$onUpdate}";
        }

        return $sql;
    }

    protected function getConstraintName(string $table): string
    {
        return "{$table}_{$this->get('column')}_foreign";
    }
}
