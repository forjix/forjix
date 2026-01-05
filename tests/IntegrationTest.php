<?php

declare(strict_types=1);

namespace Forjix\Tests;

use PHPUnit\Framework\TestCase;

class IntegrationTest extends TestCase
{
    public function testPackageStructure(): void
    {
        $packages = ['support', 'core', 'http', 'database', 'orm', 'view', 'console', 'validation'];

        foreach ($packages as $package) {
            $this->assertDirectoryExists(__DIR__ . "/../packages/{$package}");
            $this->assertFileExists(__DIR__ . "/../packages/{$package}/composer.json");
            $this->assertDirectoryExists(__DIR__ . "/../packages/{$package}/src");
        }
    }

    public function testComposerJsonValid(): void
    {
        $composerPath = __DIR__ . '/../composer.json';
        $this->assertFileExists($composerPath);

        $content = file_get_contents($composerPath);
        $json = json_decode($content, true);

        $this->assertNotNull($json);
        $this->assertEquals('forjix/forjix', $json['name']);
        $this->assertEquals('0.1.0', $json['version']);
        $this->assertEquals('GPL-3.0-or-later', $json['license']);
    }

    public function testAllPackagesHaveComposerJson(): void
    {
        $packages = glob(__DIR__ . '/../packages/*', GLOB_ONLYDIR);

        foreach ($packages as $package) {
            $composerPath = $package . '/composer.json';
            $this->assertFileExists($composerPath, "Missing composer.json in " . basename($package));

            $content = file_get_contents($composerPath);
            $json = json_decode($content, true);

            $this->assertNotNull($json, "Invalid JSON in " . basename($package));
            $this->assertArrayHasKey('name', $json);
            $this->assertArrayHasKey('version', $json);
        }
    }

    public function testAllPackagesHaveTests(): void
    {
        $packages = glob(__DIR__ . '/../packages/*', GLOB_ONLYDIR);

        foreach ($packages as $package) {
            $testsDir = $package . '/tests';
            $this->assertDirectoryExists($testsDir, "Missing tests directory in " . basename($package));
        }
    }

    public function testAllPackagesHavePhpunitXml(): void
    {
        $packages = glob(__DIR__ . '/../packages/*', GLOB_ONLYDIR);

        foreach ($packages as $package) {
            $phpunitPath = $package . '/phpunit.xml';
            $this->assertFileExists($phpunitPath, "Missing phpunit.xml in " . basename($package));
        }
    }
}
