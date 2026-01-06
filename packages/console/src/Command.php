<?php

declare(strict_types=1);

namespace Forjix\Console;

abstract class Command
{
    protected string $signature = '';
    protected string $description = '';
    protected array $arguments = [];
    protected array $options = [];
    protected Input $input;
    protected Output $output;

    public function setInput(Input $input): void
    {
        $this->input = $input;
    }

    public function setOutput(Output $output): void
    {
        $this->output = $output;
    }

    abstract public function handle(): int;

    public function getSignature(): string
    {
        return $this->signature;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getName(): string
    {
        $parts = explode(' ', $this->signature);
        return $parts[0];
    }

    // Input Helpers

    public function argument(string $key, mixed $default = null): mixed
    {
        return $this->input->argument($key, $default);
    }

    public function option(string $key, mixed $default = null): mixed
    {
        return $this->input->option($key, $default);
    }

    public function hasArgument(string $key): bool
    {
        return $this->input->hasArgument($key);
    }

    public function hasOption(string $key): bool
    {
        return $this->input->hasOption($key);
    }

    // Output Helpers

    public function line(string $string = '', string $style = ''): void
    {
        $this->output->writeln($string, $style);
    }

    public function info(string $string): void
    {
        $this->output->info($string);
    }

    public function comment(string $string): void
    {
        $this->output->comment($string);
    }

    public function question(string $string): void
    {
        $this->output->question($string);
    }

    public function error(string $string): void
    {
        $this->output->error($string);
    }

    public function warn(string $string): void
    {
        $this->output->warn($string);
    }

    public function success(string $string): void
    {
        $this->output->success($string);
    }

    public function newLine(int $count = 1): void
    {
        $this->output->newLine($count);
    }

    public function table(array $headers, array $rows): void
    {
        $this->output->table($headers, $rows);
    }

    // Interactive Helpers

    public function ask(string $question, ?string $default = null): string
    {
        return $this->output->ask($question, $default);
    }

    public function secret(string $question): string
    {
        return $this->output->secret($question);
    }

    public function confirm(string $question, bool $default = false): bool
    {
        return $this->output->confirm($question, $default);
    }

    public function choice(string $question, array $choices, ?string $default = null): string
    {
        return $this->output->choice($question, $choices, $default);
    }

    public function anticipate(string $question, array $choices, ?string $default = null): string
    {
        return $this->ask($question, $default);
    }

    // Progress Bar

    public function withProgressBar(iterable $items, callable $callback): void
    {
        $this->output->withProgressBar($items, $callback);
    }

    // Calling Other Commands

    public function call(string $command, array $arguments = []): int
    {
        return $this->getApplication()->call($command, $arguments);
    }

    public function callSilent(string $command, array $arguments = []): int
    {
        return $this->getApplication()->call($command, $arguments, true);
    }

    protected function getApplication(): Application
    {
        return Application::getInstance();
    }
}
