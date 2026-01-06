<?php

declare(strict_types=1);

namespace Forjix\Console\Commands;

use Forjix\Console\Command;

class Serve extends Command
{
    protected string $signature = 'serve {--host=127.0.0.1} {--port=8000}';
    protected string $description = 'Serve the application on the PHP development server';

    public function handle(): int
    {
        $host = $this->option('host') ?: '127.0.0.1';
        $port = $this->option('port') ?: 8000;

        $this->info("Forjix development server started: http://{$host}:{$port}");
        $this->comment('Press Ctrl+C to stop the server');

        $publicPath = getcwd() . '/public';

        if (!is_dir($publicPath)) {
            $this->error('Public directory not found.');
            return 1;
        }

        passthru(sprintf(
            'php -S %s:%s -t %s',
            $host,
            $port,
            escapeshellarg($publicPath)
        ));

        return 0;
    }
}
