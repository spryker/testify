<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerTest\Shared\Testify\Helper;

use Codeception\Module;
use RuntimeException;
use Spryker\Shared\Config\Config;
use Spryker\Shared\Propel\PropelConstants;

/**
 * Points the Zed database at a SQLite template file so integration suites run host-lane without env
 * vars or service containers. Must be listed BEFORE any module that reads the Spryker Config, and
 * an explicitly set SPRYKER_DB_ENGINE always wins. The suite runs against a copy of the template by
 * default, so the template stays pristine.
 *
 * The template is a gitignored artifact {@see \Spryker\Zed\Testify\Business\Sqlite\SqliteZedTemplateBuilder}
 * builds on demand; rebuild it by hand with
 * `vendor/bin/console testify:build:sqlite-template --force`.
 */
class SqliteDatabaseHelper extends Module
{
    /**
     * Copied rather than referenced: a Codeception module does not reach into Zed's config.
     *
     * @uses \Spryker\Zed\Propel\PropelConfig::DB_ENGINE_SQLITE
     */
    protected const string DB_ENGINE_SQLITE = 'sqlite';

    protected const string BUILD_COMMAND = 'testify:build:sqlite-template';

    protected const string CONSOLE_RELATIVE_PATH = 'vendor/bin/console';

    protected const string AUTOLOAD_RELATIVE_PATH = 'vendor/autoload.php';

    /**
     * The build command is registered only when development console commands are enabled. Every
     * environment a suite runs in enables them, but the self-heal must not depend on that being
     * configured — without the flag the command would simply not exist.
     */
    protected const string ENV_DEVELOPMENT_CONSOLE_COMMANDS = 'DEVELOPMENT_CONSOLE_COMMANDS';

    /**
     * @var array<string, mixed>
     */
    protected array $config = [
        'templateDatabasePath' => 'data/spike-zed.sqlite',
        'copyTemplate' => true,
    ];

    protected ?string $workDatabasePath = null;

    protected bool $sqliteEngineRequested = false;

    public function _initialize(): void
    {
        if (getenv('SPRYKER_DB_ENGINE') !== false) {
            return;
        }

        $templateDatabasePath = $this->resolveTemplateDatabasePath();

        if (!is_file($templateDatabasePath)) {
            $this->buildTemplateDatabase($templateDatabasePath);
        }

        if (!is_file($templateDatabasePath)) {
            $this->fail(sprintf(
                'SQLite template database not found at "%s" and the automated build did not produce it. '
                . 'See the docblock of %s for how to rebuild it.',
                $templateDatabasePath,
                static::class,
            ));
        }

        $databasePath = $templateDatabasePath;
        if ($this->config['copyTemplate']) {
            $databasePath = $this->createWorkCopy($templateDatabasePath);
            $this->workDatabasePath = $databasePath;
        }

        putenv('SPRYKER_DB_ENGINE=' . static::DB_ENGINE_SQLITE);
        putenv('SPRYKER_DB_DATABASE=' . $databasePath);
        $this->sqliteEngineRequested = true;
    }

    /**
     * The env vars set in {@see _initialize()} are silent no-ops if the Spryker Config was built
     * before them, so the resolved engine is verified once every module has initialized.
     *
     * @param array<mixed> $settings
     */
    public function _beforeSuite($settings = []): void
    {
        if (!$this->sqliteEngineRequested) {
            return;
        }

        $configuredEngine = Config::get(PropelConstants::ZED_DB_ENGINE);
        if ($configuredEngine === static::DB_ENGINE_SQLITE) {
            return;
        }

        $this->fail(sprintf(
            'The Spryker Config resolved the "%s" database engine although %s pointed the suite at '
            . 'the SQLite template — the Config was frozen before this module ran. List this module '
            . 'before any module that reads the Spryker Config in the suite\'s codeception.yml.',
            $configuredEngine,
            static::class,
        ));
    }

    public function _afterSuite(): void
    {
        if ($this->workDatabasePath === null || !file_exists($this->workDatabasePath)) {
            return;
        }

        unlink($this->workDatabasePath);
        $this->workDatabasePath = null;
    }

    protected function buildTemplateDatabase(string $templateDatabasePath): void
    {
        $applicationRootDir = $this->getApplicationRootDir();

        $command = sprintf(
            '%s %s %s --path=%s 2>&1',
            escapeshellarg(PHP_BINARY),
            escapeshellarg($applicationRootDir . '/' . static::CONSOLE_RELATIVE_PATH),
            static::BUILD_COMMAND,
            escapeshellarg($templateDatabasePath),
        );

        $environment = getenv();
        $environment[static::ENV_DEVELOPMENT_CONSOLE_COMMANDS] = '1';

        $process = proc_open($command, [1 => ['pipe', 'w']], $pipes, $applicationRootDir, $environment);

        if (!is_resource($process)) {
            $this->fail(sprintf('Unable to start "%s".', static::BUILD_COMMAND));
        }

        $commandOutput = (string)stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $exitCode = proc_close($process);

        if ($exitCode === 0) {
            return;
        }

        $this->fail(sprintf(
            "Automated SQLite template build failed (exit %d):\n%s",
            $exitCode,
            $commandOutput,
        ));
    }

    protected function resolveTemplateDatabasePath(): string
    {
        $templateDatabasePath = (string)$this->config['templateDatabasePath'];

        if (str_starts_with($templateDatabasePath, '/')) {
            return $templateDatabasePath;
        }

        return $this->getApplicationRootDir() . '/' . $templateDatabasePath;
    }

    protected function createWorkCopy(string $templateDatabasePath): string
    {
        $workDatabasePath = sys_get_temp_dir() . '/' . uniqid('sqlite-test-db-', true) . '.sqlite';

        // clonefile is instant and copy-on-write; plain copy() is the portable fallback.
        if (PHP_OS_FAMILY === 'Darwin') {
            exec(sprintf('cp -c %s %s 2>/dev/null', escapeshellarg($templateDatabasePath), escapeshellarg($workDatabasePath)), $output, $exitCode);
            if ($exitCode === 0) {
                return $workDatabasePath;
            }
        }

        copy($templateDatabasePath, $workDatabasePath);

        return $workDatabasePath;
    }

    /**
     * Walks up from this file until it finds the application root (the directory that holds
     * vendor/autoload.php), so the helper works whether it lives in a project checkout or is
     * installed into vendor/.
     *
     * @throws \RuntimeException
     */
    protected function getApplicationRootDir(): string
    {
        $directory = __DIR__;

        while (!is_file($directory . '/' . static::AUTOLOAD_RELATIVE_PATH)) {
            $parent = dirname($directory);

            if ($parent === $directory) {
                throw new RuntimeException(sprintf('Unable to locate the application root above "%s".', __DIR__));
            }

            $directory = $parent;
        }

        return $directory;
    }
}
