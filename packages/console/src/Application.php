<?php

declare(strict_types=1);

namespace Forjix\Console;

use Forjix\Core\Application as CoreApplication;

class Application
{
    protected static ?self $instance = null;

    protected string $name = 'Forjix';
    protected string $version = '1.0.0';
    protected array $commands = [];
    protected ?CoreApplication $app = null;
    protected Output $output;

    public function __construct(string $name = 'Forjix', string $version = '1.0.0')
    {
        $this->name = $name;
        $this->version = $version;
        $this->output = new Output();
        static::$instance = $this;
    }

    public static function getInstance(): ?self
    {
        return static::$instance;
    }

    public function setApplication(CoreApplication $app): void
    {
        $this->app = $app;
    }

    public function add(Command $command): void
    {
        $this->commands[$command->getName()] = $command;
    }

    public function addCommands(array $commands): void
    {
        foreach ($commands as $command) {
            $this->add($command);
        }
    }

    public function resolve(string $class): Command
    {
        if ($this->app) {
            return $this->app->make($class);
        }

        return new $class();
    }

    public function run(?array $argv = null): int
    {
        $argv = $argv ?? $_SERVER['argv'];
        $input = new Input($argv);

        $commandName = $input->getCommand();

        if ($commandName === null || $input->hasOption('help') || $input->hasOption('h')) {
            return $this->showHelp($commandName);
        }

        if ($input->hasOption('version') || $input->hasOption('V')) {
            return $this->showVersion();
        }

        if (!isset($this->commands[$commandName])) {
            $this->output->error("Command [{$commandName}] not found.");
            $this->output->newLine();
            $this->suggestCommand($commandName);
            return 1;
        }

        return $this->runCommand($this->commands[$commandName], $input);
    }

    public function call(string $commandName, array $arguments = [], bool $silent = false): int
    {
        if (!isset($this->commands[$commandName])) {
            throw new \RuntimeException("Command [{$commandName}] not found.");
        }

        $argv = ['forjix', $commandName];

        foreach ($arguments as $key => $value) {
            if (is_string($key)) {
                if (str_starts_with($key, '--')) {
                    $argv[] = "{$key}={$value}";
                } else {
                    $argv[] = "--{$key}={$value}";
                }
            } else {
                $argv[] = $value;
            }
        }

        $input = new Input($argv);
        $output = $silent ? new class extends Output {
            public function write(string $message, bool $newline = false, string $style = ''): void {}
            public function writeln(string $message = '', string $style = ''): void {}
        } : $this->output;

        $command = $this->commands[$commandName];
        $command->setInput($input);
        $command->setOutput($output);

        return $command->handle();
    }

    protected function runCommand(Command $command, Input $input): int
    {
        $this->parseSignature($command, $input);

        $command->setInput($input);
        $command->setOutput($this->output);

        try {
            return $command->handle();
        } catch (\Throwable $e) {
            $this->output->error($e->getMessage());

            if ($this->output->isVerbose()) {
                $this->output->newLine();
                $this->output->writeln($e->getTraceAsString());
            }

            return 1;
        }
    }

    protected function parseSignature(Command $command, Input $input): void
    {
        $signature = $command->getSignature();

        // Parse arguments from signature: command {arg} {arg?} {arg=default}
        preg_match_all('/\{(\w+)(\?)?(?:=([^}]+))?\}/', $signature, $matches, PREG_SET_ORDER);

        $argIndex = 1; // Start from 1, index 0 is command name

        foreach ($matches as $match) {
            $name = $match[1];
            $optional = isset($match[2]) && $match[2] === '?';
            $default = $match[3] ?? null;

            $value = $input->argument($argIndex);

            if ($value !== null) {
                $input->setArgument($name, $value);
            } elseif ($default !== null) {
                $input->setArgument($name, $default);
            } elseif (!$optional) {
                // Required argument missing
            }

            $argIndex++;
        }

        // Parse options from signature: {--option} {--option=} {--o|option}
        preg_match_all('/\{--(\w\|)?(\w+)(=)?([^}]*)?\}/', $signature, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $shortcut = isset($match[1]) ? rtrim($match[1], '|') : null;
            $name = $match[2];
            $hasValue = isset($match[3]);
            $default = $match[4] ?? null;

            // Check both long and short options
            $value = $input->option($name);
            if ($value === null && $shortcut) {
                $value = $input->option($shortcut);
            }

            if ($value !== null) {
                $input->setOption($name, $value);
            } elseif ($default) {
                $input->setOption($name, $default);
            }
        }
    }

    protected function showHelp(?string $commandName = null): int
    {
        if ($commandName && isset($this->commands[$commandName])) {
            $this->showCommandHelp($this->commands[$commandName]);
            return 0;
        }

        $this->output->writeln("<info>{$this->name}</info> version <comment>{$this->version}</comment>");
        $this->output->newLine();
        $this->output->writeln('<comment>Usage:</comment>');
        $this->output->writeln('  command [options] [arguments]');
        $this->output->newLine();
        $this->output->writeln('<comment>Available commands:</comment>');

        // Group commands by namespace
        $groups = ['_global' => []];

        foreach ($this->commands as $name => $command) {
            if (str_contains($name, ':')) {
                [$namespace] = explode(':', $name, 2);
                $groups[$namespace][] = $command;
            } else {
                $groups['_global'][] = $command;
            }
        }

        ksort($groups);

        foreach ($groups as $namespace => $commands) {
            if ($namespace !== '_global') {
                $this->output->writeln(" <comment>{$namespace}</comment>");
            }

            foreach ($commands as $command) {
                $this->output->writeln(sprintf(
                    '  <info>%-20s</info> %s',
                    $command->getName(),
                    $command->getDescription()
                ));
            }
        }

        return 0;
    }

    protected function showCommandHelp(Command $command): void
    {
        $this->output->writeln('<comment>Description:</comment>');
        $this->output->writeln('  ' . $command->getDescription());
        $this->output->newLine();
        $this->output->writeln('<comment>Usage:</comment>');
        $this->output->writeln('  ' . $command->getSignature());
    }

    protected function showVersion(): int
    {
        $this->output->writeln("{$this->name} <info>{$this->version}</info>");
        return 0;
    }

    protected function suggestCommand(string $name): void
    {
        $alternatives = [];

        foreach ($this->commands as $commandName => $command) {
            $distance = levenshtein($name, $commandName);

            if ($distance <= 3) {
                $alternatives[$commandName] = $distance;
            }
        }

        if (!empty($alternatives)) {
            asort($alternatives);
            $suggestions = array_keys($alternatives);

            $this->output->writeln('<comment>Did you mean one of these?</comment>');
            foreach (array_slice($suggestions, 0, 3) as $suggestion) {
                $this->output->writeln("  <info>{$suggestion}</info>");
            }
        }
    }

    public function getCommands(): array
    {
        return $this->commands;
    }

    public function getCommand(string $name): ?Command
    {
        return $this->commands[$name] ?? null;
    }

    public function hasCommand(string $name): bool
    {
        return isset($this->commands[$name]);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function setOutput(Output $output): void
    {
        $this->output = $output;
    }

    public function getOutput(): Output
    {
        return $this->output;
    }
}
