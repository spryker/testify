<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Shared\Testify\Command;

use Codeception\Command\Run;
use Codeception\Configuration;
use Codeception\CustomCommandInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Codeception config consolidation: one root config + CLI flags.
 *
 * Drop-in replacement for `codecept run` that pre-selects which included apps
 * (leaf codeception.yml directories) are booted, instead of booting every one
 * of the ~988 apps and letting `-g`/`-x` filter tests afterwards. Adds two
 * path axes (`--include`/`--exclude`) and maps namespace-shaped `-g`/`-x`
 * groups onto app paths (see {@link AppPathSelector}). Native `-g`/`-x` are
 * still forwarded to Codeception unchanged, so test selection is identical —
 * this only avoids booting suites that would run nothing, and gates the
 * per-app header so the log contains only apps that actually ran.
 *
 * Replaces the 9 root configs + SuiteFilterHelper: e.g.
 * `codecept run:filtered -g Glue` ~ codeception.api.yml;
 * `codecept run:filtered -x Presentation -x Glue` ~ codeception.functional.yml;
 * `codecept run:filtered --include 'src/Spryker/Cart/*'` for one module.
 */
class RunFilteredCommand extends Run implements CustomCommandInterface
{
    protected bool $appFilterApplied = false;

    public static function getCommandName(): string
    {
        return 'run:filtered';
    }

    public function getDescription(): string
    {
        return 'Runs test suites with app-path pre-selection (--include/--exclude path globs; namespace-shaped -g/-x map to app paths).';
    }

    protected function configure(): void
    {
        parent::configure();

        $this->setDescription($this->getDescription());
        $this->addOption(
            'include',
            null,
            InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
            'Only boot included apps whose relative path matches this glob (repeatable).',
        );
        $this->addOption(
            'exclude',
            null,
            InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
            'Skip included apps whose relative path matches this glob (repeatable).',
        );
    }

    /**
     * Mirrors {@see Run::runIncludedSuites()} but (1) pre-selects the app list
     * once, at the top-level call, via {@see AppPathSelector}, and (2) prints
     * the magenta per-app header only when the app has at least one runnable
     * suite — so filtered-out apps produce no output at all.
     *
     * @param array<string> $suites
     * @param array<string, array<string>> $filterAppSuites
     * @param array<string> $filterSuitesByWildcard
     */
    protected function runIncludedSuites(
        array $suites,
        string $parentDir,
        array $filterAppSuites = [],
        array $filterSuitesByWildcard = []
    ): void {
        if (!$this->appFilterApplied) {
            $this->appFilterApplied = true;
            $suites = $this->selectApps($suites);
        }

        $defaultConfig = Configuration::config();
        $absolutePath = Configuration::projectDir();

        foreach ($suites as $relativePath) {
            $currentDir = rtrim($parentDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $relativePath;
            $config = Configuration::config($currentDir);

            if (!empty($defaultConfig['groups'])) {
                $groups = array_map(fn ($group): string => $absolutePath . $group, $defaultConfig['groups']);
                Configuration::append(['groups' => $groups]);
            }

            $appSuites = Configuration::suites();

            if ($filterSuitesByWildcard !== []) {
                $appSuites = array_intersect($appSuites, $filterSuitesByWildcard);
            }
            if (isset($filterAppSuites[$relativePath])) {
                $appSuites = array_intersect($appSuites, $filterAppSuites[$relativePath]);
            }

            if ($this->hasRunnableSuite($appSuites)) {
                $namespace = $this->currentNamespace();
                $this->output->writeln(
                    "\n<fg=white;bg=magenta>\n[{$namespace}]: tests from {$currentDir}\n</fg=white;bg=magenta>",
                );

                $this->executed += $this->runSuites($appSuites, $this->options['skip']);
            }

            if (!empty($config['include'])) {
                $this->runIncludedSuites($config['include'], $currentDir);
            }
        }
    }

    /**
     * @param array<string> $appPaths
     *
     * @return array<string>
     */
    protected function selectApps(array $appPaths): array
    {
        $selected = (new AppPathSelector())->select(
            $appPaths,
            (array)($this->options['include'] ?? []),
            (array)($this->options['exclude'] ?? []),
            (array)($this->options['group'] ?? []),
            (array)($this->options['skip-group'] ?? []),
        );

        if (empty($this->options['silent']) && $this->output !== null) {
            $this->output->writeln(sprintf(
                '[run:filtered] <info>%d</info> of %d included apps selected.',
                count($selected),
                count($appPaths),
            ));
        }

        return $selected;
    }

    /**
     * @param array<string> $appSuites
     */
    protected function hasRunnableSuite(array $appSuites): bool
    {
        $skipped = (array)($this->options['skip'] ?? []);
        $available = Configuration::suites();
        foreach ($appSuites as $suite) {
            if (!in_array($suite, $skipped, true) && in_array($suite, $available, true)) {
                return true;
            }
        }

        return false;
    }
}
