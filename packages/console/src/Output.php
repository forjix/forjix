<?php

declare(strict_types=1);

namespace Forjix\Console;

class Output
{
    public const VERBOSITY_QUIET = 0;
    public const VERBOSITY_NORMAL = 1;
    public const VERBOSITY_VERBOSE = 2;
    public const VERBOSITY_VERY_VERBOSE = 3;
    public const VERBOSITY_DEBUG = 4;

    protected int $verbosity = self::VERBOSITY_NORMAL;
    protected bool $decorated = true;

    protected array $styles = [
        'info' => ['fg' => 'green'],
        'comment' => ['fg' => 'yellow'],
        'question' => ['fg' => 'black', 'bg' => 'cyan'],
        'error' => ['fg' => 'white', 'bg' => 'red'],
        'warning' => ['fg' => 'black', 'bg' => 'yellow'],
        'success' => ['fg' => 'black', 'bg' => 'green'],
    ];

    protected array $foregroundColors = [
        'black' => '30',
        'red' => '31',
        'green' => '32',
        'yellow' => '33',
        'blue' => '34',
        'magenta' => '35',
        'cyan' => '36',
        'white' => '37',
        'default' => '39',
    ];

    protected array $backgroundColors = [
        'black' => '40',
        'red' => '41',
        'green' => '42',
        'yellow' => '43',
        'blue' => '44',
        'magenta' => '45',
        'cyan' => '46',
        'white' => '47',
        'default' => '49',
    ];

    public function __construct()
    {
        $this->decorated = $this->hasColorSupport();
    }

    protected function hasColorSupport(): bool
    {
        if (isset($_SERVER['NO_COLOR']) || getenv('NO_COLOR') !== false) {
            return false;
        }

        if (DIRECTORY_SEPARATOR === '\\') {
            return getenv('ANSICON') !== false
                || getenv('ConEmuANSI') === 'ON'
                || getenv('TERM') === 'xterm';
        }

        return stream_isatty(STDOUT);
    }

    public function write(string $message, bool $newline = false, string $style = ''): void
    {
        if ($style && $this->decorated) {
            $message = $this->applyStyle($message, $style);
        }

        echo $message;

        if ($newline) {
            echo PHP_EOL;
        }
    }

    public function writeln(string $message = '', string $style = ''): void
    {
        $this->write($message, true, $style);
    }

    protected function applyStyle(string $message, string $style): string
    {
        $codes = [];

        if (isset($this->styles[$style])) {
            $style = $this->styles[$style];
        } else {
            return $message;
        }

        if (isset($style['fg']) && isset($this->foregroundColors[$style['fg']])) {
            $codes[] = $this->foregroundColors[$style['fg']];
        }

        if (isset($style['bg']) && isset($this->backgroundColors[$style['bg']])) {
            $codes[] = $this->backgroundColors[$style['bg']];
        }

        if (isset($style['bold'])) {
            $codes[] = '1';
        }

        if (empty($codes)) {
            return $message;
        }

        return sprintf("\033[%sm%s\033[0m", implode(';', $codes), $message);
    }

    public function info(string $message): void
    {
        $this->writeln($message, 'info');
    }

    public function comment(string $message): void
    {
        $this->writeln($message, 'comment');
    }

    public function question(string $message): void
    {
        $this->writeln($message, 'question');
    }

    public function error(string $message): void
    {
        $this->writeln($message, 'error');
    }

    public function warn(string $message): void
    {
        $this->writeln($message, 'warning');
    }

    public function success(string $message): void
    {
        $this->writeln($message, 'success');
    }

    public function newLine(int $count = 1): void
    {
        echo str_repeat(PHP_EOL, $count);
    }

    public function table(array $headers, array $rows): void
    {
        // Calculate column widths
        $widths = [];

        foreach ($headers as $i => $header) {
            $widths[$i] = strlen((string) $header);
        }

        foreach ($rows as $row) {
            foreach ($row as $i => $cell) {
                $widths[$i] = max($widths[$i] ?? 0, strlen((string) $cell));
            }
        }

        // Print separator
        $separator = '+';
        foreach ($widths as $width) {
            $separator .= str_repeat('-', $width + 2) . '+';
        }

        $this->writeln($separator);

        // Print headers
        $line = '|';
        foreach ($headers as $i => $header) {
            $line .= ' ' . str_pad((string) $header, $widths[$i]) . ' |';
        }
        $this->writeln($line);
        $this->writeln($separator);

        // Print rows
        foreach ($rows as $row) {
            $line = '|';
            foreach ($row as $i => $cell) {
                $line .= ' ' . str_pad((string) $cell, $widths[$i]) . ' |';
            }
            $this->writeln($line);
        }

        $this->writeln($separator);
    }

    public function ask(string $question, ?string $default = null): string
    {
        $defaultText = $default !== null ? " [{$default}]" : '';
        $this->write("<info>{$question}</info>{$defaultText}: ");

        $answer = trim((string) fgets(STDIN));

        return $answer !== '' ? $answer : ($default ?? '');
    }

    public function secret(string $question): string
    {
        $this->write("<info>{$question}</info>: ");

        if (DIRECTORY_SEPARATOR === '\\') {
            // Windows
            $answer = trim((string) fgets(STDIN));
        } else {
            // Unix
            system('stty -echo');
            $answer = trim((string) fgets(STDIN));
            system('stty echo');
            $this->newLine();
        }

        return $answer;
    }

    public function confirm(string $question, bool $default = false): bool
    {
        $defaultText = $default ? 'Y/n' : 'y/N';
        $this->write("<info>{$question}</info> [{$defaultText}]: ");

        $answer = strtolower(trim((string) fgets(STDIN)));

        if ($answer === '') {
            return $default;
        }

        return in_array($answer, ['y', 'yes', '1', 'true'], true);
    }

    public function choice(string $question, array $choices, ?string $default = null): string
    {
        $this->writeln("<info>{$question}</info>");

        foreach ($choices as $key => $choice) {
            $defaultMarker = $choice === $default ? ' (default)' : '';
            $this->writeln("  [{$key}] {$choice}{$defaultMarker}");
        }

        $answer = $this->ask('Enter your choice', $default);

        if (isset($choices[$answer])) {
            return $choices[$answer];
        }

        if (in_array($answer, $choices, true)) {
            return $answer;
        }

        return $default ?? (string) reset($choices);
    }

    public function withProgressBar(iterable $items, callable $callback): void
    {
        $items = is_array($items) ? $items : iterator_to_array($items);
        $total = count($items);
        $current = 0;
        $barWidth = 50;

        foreach ($items as $key => $item) {
            $callback($item, $key);
            $current++;

            $progress = $current / $total;
            $filled = (int) ($progress * $barWidth);
            $empty = $barWidth - $filled;

            $bar = str_repeat('=', $filled) . '>' . str_repeat(' ', max(0, $empty - 1));
            $percent = (int) ($progress * 100);

            $this->write("\r[{$bar}] {$percent}% ({$current}/{$total})");
        }

        $this->newLine();
    }

    public function setVerbosity(int $level): void
    {
        $this->verbosity = $level;
    }

    public function getVerbosity(): int
    {
        return $this->verbosity;
    }

    public function isQuiet(): bool
    {
        return $this->verbosity === self::VERBOSITY_QUIET;
    }

    public function isVerbose(): bool
    {
        return $this->verbosity >= self::VERBOSITY_VERBOSE;
    }

    public function isVeryVerbose(): bool
    {
        return $this->verbosity >= self::VERBOSITY_VERY_VERBOSE;
    }

    public function isDebug(): bool
    {
        return $this->verbosity >= self::VERBOSITY_DEBUG;
    }

    public function setDecorated(bool $decorated): void
    {
        $this->decorated = $decorated;
    }

    public function isDecorated(): bool
    {
        return $this->decorated;
    }
}
