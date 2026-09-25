<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace Spryker\Zed\Testify\Business\Sqlite;

use ErrorException;
use PDO;
use PDOException;
use Spryker\Shared\Testify\SystemUnderTestBootstrap;
use Spryker\Zed\Testify\Exception\SqliteZedTemplateBuildFailedException;

/**
 * Builds a reproducible SQLite Zed template database for host-lane integration suites, with no
 * docker and no hand-written SQL: the propel schema build against the SQLite engine, then the
 * standard bring-up, then a row-count check. It carries only that foundational data — tests provide
 * everything they assert against themselves.
 *
 * Every step runs as a child process, because each has to boot against the SQLite engine while the
 * calling process's Spryker Config is already frozen.
 */
class SqliteZedTemplateBuilder implements SqliteZedTemplateBuilderInterface
{
    /**
     * Copied rather than referenced: a Business model does not reach into a foreign module's config.
     *
     * @uses \Spryker\Zed\Propel\PropelConfig::DB_ENGINE_SQLITE
     */
    protected const string DB_ENGINE = 'sqlite';

    protected const string CONSOLE_RELATIVE_PATH = 'vendor/bin/console';

    protected const string ENV_DB_ENGINE = 'SPRYKER_DB_ENGINE';

    protected const string ENV_DB_DATABASE = 'SPRYKER_DB_DATABASE';

    protected const string ENV_APPLICATION_ENV = 'APPLICATION_ENV';

    protected const int DIRECTORY_PERMISSIONS = 0777;

    /**
     * The propel commands that build the full Zed schema into the (empty) target database.
     *
     * @var list<string>
     */
    protected const array PROPEL_COMMANDS = [
        'propel:database:create',
        'propel:diff',
        'propel:migrate',
    ];

    /**
     * The standard bring-up that populates the schema with the foundational data a booted store
     * needs: setup:init-db loads the installer plugins (locale, country, oauth, …), then the
     * store/currency/locale importers add the rows the kernel resolves at boot. Ordered by
     * dependency (stores and currencies before the relations that link them).
     *
     * @var list<string>
     */
    protected const array INSTALL_COMMANDS = [
        'setup:init-db',
        'data:import:store',
        'data:import:currency',
        'data:import:currency-store',
        'data:import:locale-store',
    ];

    /**
     * Minimum row counts the finished template must contain — a post-build sanity check that the
     * schema build and the bring-up both landed the data the kernel resolves at boot.
     *
     * @var array<string, int>
     */
    protected const array EXPECTED_MINIMUM_ROW_COUNTS = [
        'spy_store' => 1,
        'spy_locale' => 1,
        'spy_currency' => 1,
    ];

    public function __construct(
        protected string $applicationRootDir,
        protected string $migrationDirectory,
        protected string $defaultTemplatePath,
    ) {
    }

    public function build(?string $templatePath): string
    {
        $templatePath ??= $this->defaultTemplatePath;

        if ($this->hasTemplate($templatePath)) {
            return sprintf(
                'SQLite Zed template already present at "%s" (pass --force to rebuild). Skipping.',
                $templatePath,
            );
        }

        return $this->buildTemplate($templatePath);
    }

    public function rebuild(?string $templatePath): string
    {
        return $this->buildTemplate($templatePath ?? $this->defaultTemplatePath);
    }

    /**
     * @throws \Spryker\Zed\Testify\Exception\SqliteZedTemplateBuildFailedException
     */
    protected function buildTemplate(string $templatePath): string
    {
        try {
            $this->prepareTargetDirectory($templatePath);
            $this->removeExistingTemplate($templatePath);
            $this->clearMigrationDirectory();
            $this->runCommands(static::PROPEL_COMMANDS, $templatePath);
            $this->runCommands(static::INSTALL_COMMANDS, $templatePath);
            $this->verify($templatePath);
        } catch (ErrorException | PDOException $exception) {
            // Zed installs an error handler that promotes PHP warnings to ErrorException, so a
            // failing mkdir/unlink/proc_open never reaches the return-value guards below. Both of
            // these mean the same thing to a caller as the guards do: the template was not built.
            throw new SqliteZedTemplateBuildFailedException(
                sprintf('Building the SQLite Zed template at "%s" failed: %s', $templatePath, $exception->getMessage()),
                0,
                $exception,
            );
        }

        return sprintf('SQLite Zed template built at "%s".', $templatePath);
    }

    /**
     * An empty file does not count: booting anything against the target path creates one, and
     * treating that as a finished template would skip the build and fail the suite instead.
     */
    protected function hasTemplate(string $templatePath): bool
    {
        return is_file($templatePath) && filesize($templatePath) > 0;
    }

    protected function prepareTargetDirectory(string $templatePath): void
    {
        $directory = dirname($templatePath);

        if (!is_dir($directory) && !mkdir($directory, static::DIRECTORY_PERMISSIONS, true) && !is_dir($directory)) {
            throw new SqliteZedTemplateBuildFailedException(sprintf('Unable to create target directory "%s".', $directory));
        }
    }

    protected function removeExistingTemplate(string $templatePath): void
    {
        if (!is_file($templatePath)) {
            return;
        }

        unlink($templatePath);
    }

    protected function clearMigrationDirectory(): void
    {
        foreach (glob($this->migrationDirectory . '/*.php') ?: [] as $migrationFile) {
            unlink($migrationFile);
        }
    }

    /**
     * @param list<string> $commands
     */
    protected function runCommands(array $commands, string $templatePath): void
    {
        foreach ($commands as $command) {
            $this->runConsoleCommand($command, $templatePath);
        }
    }

    protected function runConsoleCommand(string $command, string $templatePath): void
    {
        $consolePath = $this->applicationRootDir . '/' . static::CONSOLE_RELATIVE_PATH;
        $fullCommand = sprintf('%s %s %s', escapeshellarg(PHP_BINARY), escapeshellarg($consolePath), $command);

        $environment = getenv();
        $environment[static::ENV_DB_ENGINE] = static::DB_ENGINE;
        $environment[static::ENV_DB_DATABASE] = $templatePath;
        // The template is built for the test environment, so the child console loads that
        // environment's configuration rather than whatever the calling shell happens to carry.
        $environment[static::ENV_APPLICATION_ENV] = SystemUnderTestBootstrap::TEST_ENVIRONMENT;

        $descriptorSpecification = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($fullCommand, $descriptorSpecification, $pipes, $this->applicationRootDir, $environment);

        if (!is_resource($process)) {
            throw new SqliteZedTemplateBuildFailedException(sprintf('Unable to start console command "%s".', $command));
        }

        fclose($pipes[0]);
        $standardOutput = (string)stream_get_contents($pipes[1]);
        $errorOutput = (string)stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            throw new SqliteZedTemplateBuildFailedException(sprintf(
                "Console command \"%s\" failed (exit %d):\n%s\n%s",
                $command,
                $exitCode,
                $standardOutput,
                $errorOutput,
            ));
        }
    }

    protected function verify(string $templatePath): void
    {
        $connection = $this->createConnection($templatePath);

        foreach (static::EXPECTED_MINIMUM_ROW_COUNTS as $table => $minimumRowCount) {
            $statement = $connection->query(sprintf('SELECT count(*) FROM %s', $table));
            $actualRowCount = $statement === false ? 0 : (int)$statement->fetchColumn();

            if ($actualRowCount < $minimumRowCount) {
                throw new SqliteZedTemplateBuildFailedException(sprintf(
                    'Template verification failed: expected at least %d row(s) in "%s", found %d.',
                    $minimumRowCount,
                    $table,
                    $actualRowCount,
                ));
            }
        }
    }

    protected function createConnection(string $templatePath): PDO
    {
        $connection = new PDO('sqlite:' . $templatePath);
        $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $connection;
    }
}
