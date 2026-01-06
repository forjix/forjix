<?php

declare(strict_types=1);

namespace Forjix\Database\Schema;

use Forjix\Support\Fluent;

class Blueprint
{
    protected string $table;
    protected array $columns = [];
    protected array $commands = [];
    protected string $engine = 'InnoDB';
    protected ?string $charset = 'utf8mb4';
    protected ?string $collation = 'utf8mb4_unicode_ci';
    protected bool $temporary = false;

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    public function build(string $driver = 'mysql'): array
    {
        return [$this->toSql($driver)];
    }

    public function toSql(string $driver = 'mysql'): string
    {
        $columns = array_map(fn($column) => $this->compileColumn($column, $driver), $this->columns);

        $sql = sprintf(
            "create table %s%s (\n    %s\n)",
            $this->temporary ? 'temporary ' : '',
            $this->table,
            implode(",\n    ", $columns)
        );

        if ($driver === 'mysql') {
            $sql .= " engine={$this->engine} default charset={$this->charset} collate={$this->collation}";
        }

        return $sql;
    }

    protected function compileColumn(Fluent $column, string $driver): string
    {
        $sql = $column->name . ' ' . $this->getColumnType($column, $driver);

        if ($column->unsigned && $driver === 'mysql') {
            $sql .= ' unsigned';
        }

        if ($column->nullable) {
            $sql .= ' null';
        } else {
            $sql .= ' not null';
        }

        if ($column->default !== null) {
            $default = is_string($column->default) ? "'{$column->default}'" : $column->default;
            $sql .= " default {$default}";
        }

        if ($column->autoIncrement) {
            $sql .= $driver === 'mysql' ? ' auto_increment primary key' : ' primary key autoincrement';
        }

        if ($column->primary && !$column->autoIncrement) {
            $sql .= ' primary key';
        }

        if ($column->unique && !$column->primary) {
            $sql .= ' unique';
        }

        return $sql;
    }

    protected function getColumnType(Fluent $column, string $driver): string
    {
        return match ($column->type) {
            'bigInteger' => $driver === 'sqlite' ? 'integer' : 'bigint',
            'integer' => $driver === 'sqlite' ? 'integer' : 'int',
            'smallInteger' => $driver === 'sqlite' ? 'integer' : 'smallint',
            'tinyInteger' => $driver === 'sqlite' ? 'integer' : 'tinyint',
            'string' => "varchar({$column->length})",
            'text' => 'text',
            'mediumText' => $driver === 'mysql' ? 'mediumtext' : 'text',
            'longText' => $driver === 'mysql' ? 'longtext' : 'text',
            'boolean' => $driver === 'mysql' ? 'tinyint(1)' : 'boolean',
            'date' => 'date',
            'dateTime' => $driver === 'sqlite' ? 'datetime' : 'datetime',
            'timestamp' => $driver === 'sqlite' ? 'datetime' : 'timestamp',
            'time' => 'time',
            'decimal' => "decimal({$column->precision},{$column->scale})",
            'float' => $driver === 'mysql' ? "float({$column->precision},{$column->scale})" : 'real',
            'double' => $driver === 'mysql' ? "double({$column->precision},{$column->scale})" : 'real',
            'json' => $driver === 'mysql' ? 'json' : 'text',
            'uuid' => $driver === 'mysql' ? 'char(36)' : 'varchar(36)',
            'binary' => 'blob',
            'enum' => "enum('" . implode("','", $column->allowed) . "')",
            default => $column->type,
        };
    }

    public function id(string $column = 'id'): Fluent
    {
        return $this->bigIncrements($column);
    }

    public function bigIncrements(string $column): Fluent
    {
        return $this->unsignedBigInteger($column)->autoIncrement();
    }

    public function increments(string $column): Fluent
    {
        return $this->unsignedInteger($column)->autoIncrement();
    }

    public function bigInteger(string $column): Fluent
    {
        return $this->addColumn('bigInteger', $column);
    }

    public function unsignedBigInteger(string $column): Fluent
    {
        return $this->bigInteger($column)->unsigned();
    }

    public function integer(string $column): Fluent
    {
        return $this->addColumn('integer', $column);
    }

    public function unsignedInteger(string $column): Fluent
    {
        return $this->integer($column)->unsigned();
    }

    public function smallInteger(string $column): Fluent
    {
        return $this->addColumn('smallInteger', $column);
    }

    public function unsignedSmallInteger(string $column): Fluent
    {
        return $this->smallInteger($column)->unsigned();
    }

    public function tinyInteger(string $column): Fluent
    {
        return $this->addColumn('tinyInteger', $column);
    }

    public function unsignedTinyInteger(string $column): Fluent
    {
        return $this->tinyInteger($column)->unsigned();
    }

    public function string(string $column, int $length = 255): Fluent
    {
        return $this->addColumn('string', $column, ['length' => $length]);
    }

    public function text(string $column): Fluent
    {
        return $this->addColumn('text', $column);
    }

    public function mediumText(string $column): Fluent
    {
        return $this->addColumn('mediumText', $column);
    }

    public function longText(string $column): Fluent
    {
        return $this->addColumn('longText', $column);
    }

    public function boolean(string $column): Fluent
    {
        return $this->addColumn('boolean', $column);
    }

    public function date(string $column): Fluent
    {
        return $this->addColumn('date', $column);
    }

    public function dateTime(string $column, int $precision = 0): Fluent
    {
        return $this->addColumn('dateTime', $column, ['precision' => $precision]);
    }

    public function timestamp(string $column, int $precision = 0): Fluent
    {
        return $this->addColumn('timestamp', $column, ['precision' => $precision]);
    }

    public function timestamps(int $precision = 0): void
    {
        $this->timestamp('created_at', $precision)->nullable();
        $this->timestamp('updated_at', $precision)->nullable();
    }

    public function softDeletes(string $column = 'deleted_at', int $precision = 0): Fluent
    {
        return $this->timestamp($column, $precision)->nullable();
    }

    public function time(string $column, int $precision = 0): Fluent
    {
        return $this->addColumn('time', $column, ['precision' => $precision]);
    }

    public function decimal(string $column, int $precision = 8, int $scale = 2): Fluent
    {
        return $this->addColumn('decimal', $column, compact('precision', 'scale'));
    }

    public function float(string $column, int $precision = 8, int $scale = 2): Fluent
    {
        return $this->addColumn('float', $column, compact('precision', 'scale'));
    }

    public function double(string $column, int $precision = 8, int $scale = 2): Fluent
    {
        return $this->addColumn('double', $column, compact('precision', 'scale'));
    }

    public function json(string $column): Fluent
    {
        return $this->addColumn('json', $column);
    }

    public function uuid(string $column = 'uuid'): Fluent
    {
        return $this->addColumn('uuid', $column);
    }

    public function binary(string $column): Fluent
    {
        return $this->addColumn('binary', $column);
    }

    public function enum(string $column, array $allowed): Fluent
    {
        return $this->addColumn('enum', $column, ['allowed' => $allowed]);
    }

    public function foreignId(string $column): Fluent
    {
        return $this->unsignedBigInteger($column);
    }

    public function rememberToken(): Fluent
    {
        return $this->string('remember_token', 100)->nullable();
    }

    protected function addColumn(string $type, string $name, array $parameters = []): Fluent
    {
        $column = new Fluent(array_merge([
            'type' => $type,
            'name' => $name,
            'nullable' => false,
            'default' => null,
            'autoIncrement' => false,
            'unsigned' => false,
            'primary' => false,
            'unique' => false,
        ], $parameters));

        $this->columns[] = $column;

        return $column;
    }

    public function dropColumn(string|array $columns): static
    {
        $this->commands[] = ['type' => 'dropColumn', 'columns' => (array) $columns];

        return $this;
    }

    public function renameColumn(string $from, string $to): static
    {
        $this->commands[] = ['type' => 'renameColumn', 'from' => $from, 'to' => $to];

        return $this;
    }

    public function primary(string|array $columns, ?string $name = null): static
    {
        $this->commands[] = ['type' => 'primary', 'columns' => (array) $columns, 'name' => $name];

        return $this;
    }

    public function unique(string|array $columns, ?string $name = null): static
    {
        $this->commands[] = ['type' => 'unique', 'columns' => (array) $columns, 'name' => $name];

        return $this;
    }

    public function index(string|array $columns, ?string $name = null): static
    {
        $this->commands[] = ['type' => 'index', 'columns' => (array) $columns, 'name' => $name];

        return $this;
    }

    public function foreign(string $column): ForeignKeyDefinition
    {
        $foreign = new ForeignKeyDefinition(['column' => $column]);
        $this->commands[] = ['type' => 'foreign', 'definition' => $foreign];

        return $foreign;
    }

    public function dropPrimary(?string $name = null): static
    {
        $this->commands[] = ['type' => 'dropPrimary', 'name' => $name];

        return $this;
    }

    public function dropUnique(string $name): static
    {
        $this->commands[] = ['type' => 'dropUnique', 'name' => $name];

        return $this;
    }

    public function dropIndex(string $name): static
    {
        $this->commands[] = ['type' => 'dropIndex', 'name' => $name];

        return $this;
    }

    public function dropForeign(string $name): static
    {
        $this->commands[] = ['type' => 'dropForeign', 'name' => $name];

        return $this;
    }

    public function temporary(): static
    {
        $this->temporary = true;

        return $this;
    }

    public function engine(string $engine): static
    {
        $this->engine = $engine;

        return $this;
    }

    public function charset(string $charset): static
    {
        $this->charset = $charset;

        return $this;
    }

    public function collation(string $collation): static
    {
        $this->collation = $collation;

        return $this;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getCommands(): array
    {
        return $this->commands;
    }
}
