<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Testify\Communication\Console;

use Spryker\Zed\Kernel\Communication\Console\Console;
use Spryker\Zed\Testify\Exception\SqliteZedTemplateBuildFailedException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @method \Spryker\Zed\Testify\Business\TestifyFacadeInterface getFacade()
 */
class BuildSqliteTemplateConsole extends Console
{
    protected const string COMMAND_NAME = 'testify:build:sqlite-template';

    protected const string OPTION_PATH = 'path';

    protected const string OPTION_FORCE = 'force';

    protected const string OPTION_FORCE_SHORTCUT = 'f';

    protected function configure(): void
    {
        $this
            ->setName(static::COMMAND_NAME)
            ->setDescription('Builds the SQLite Zed template database that host-lane integration suites run against.')
            ->addOption(
                static::OPTION_PATH,
                null,
                InputOption::VALUE_REQUIRED,
                'Absolute path to build the template at. Uses the configured default when omitted.',
            )
            ->addOption(
                static::OPTION_FORCE,
                static::OPTION_FORCE_SHORTCUT,
                InputOption::VALUE_NONE,
                'Drop and rebuild an existing template. Use this when the Zed schema drifts.',
            )
            ->setHelp(<<<'HELP'
Runs the propel schema build (database:create / diff / migrate) against the SQLite engine, then the
standard store bring-up, and verifies the foundational store/locale/currency rows landed. Needs no
docker and no database server. An existing template is left alone unless <info>--force</info> is passed.

  <info>%command.full_name%</info>                          build the default template if it is missing
  <info>%command.full_name% --force</info>                  rebuild it from scratch
  <info>%command.full_name% --path=/tmp/zed.sqlite</info>   build it somewhere else

Needs the generated code in place first: <info>vendor/bin/console propel:model:build</info> and friends.
HELP);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string|null $templatePath */
        $templatePath = $input->getOption(static::OPTION_PATH);

        try {
            $message = $input->getOption(static::OPTION_FORCE)
                ? $this->getFacade()->rebuildSqliteZedTemplate($templatePath)
                : $this->getFacade()->buildSqliteZedTemplate($templatePath);
        } catch (SqliteZedTemplateBuildFailedException $exception) {
            $output->writeln(sprintf('<fg=red>%s</>', $exception->getMessage()));

            return static::CODE_ERROR;
        }

        $output->writeln(sprintf('<fg=green>%s</>', $message));

        return static::CODE_SUCCESS;
    }
}
