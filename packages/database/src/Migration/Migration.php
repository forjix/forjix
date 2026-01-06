<?php

declare(strict_types=1);

namespace Forjix\Database\Migration;

use Forjix\Database\Connection;
use Forjix\Database\Schema\Schema;

abstract class Migration
{
    protected Connection $connection;
    protected Schema $schema;

    public function setConnection(Connection $connection): void
    {
        $this->connection = $connection;
        $this->schema = new Schema($connection);
    }

    abstract public function up(): void;

    public function down(): void
    {
        // Optional: override to implement rollback
    }
}
