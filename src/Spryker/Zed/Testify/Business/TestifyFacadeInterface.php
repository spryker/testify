<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Testify\Business;

/**
 * @api
 *
 * @method \Spryker\Zed\Testify\Business\TestifyBusinessFactory getFactory()
 */
interface TestifyFacadeInterface
{
    /**
     * Specification:
     * - Removes all files in configured directories.
     * - Directories are configured in {@link \Spryker\Zed\Testify\TestifyConfig::getOutputDirectoriesForCleanup()}.
     *
     * @api
     *
     * @return array<string>
     */
    public function cleanUpOutputDirectories(): array;

    /**
     * Specification:
     * - Builds the SQLite Zed template database that host-lane integration suites run against.
     * - Runs the propel schema build and the standard store bring-up as child processes against the
     *   SQLite engine, then verifies the foundational rows are present.
     * - Leaves an existing non-empty template alone.
     * - Target path defaults to {@link \Spryker\Zed\Testify\TestifyConfig::getSqliteZedTemplatePath()}.
     *
     * @api
     *
     * @param string|null $templatePath
     *
     * @throws \Spryker\Zed\Testify\Exception\SqliteZedTemplateBuildFailedException
     *
     * @return string
     */
    public function buildSqliteZedTemplate(?string $templatePath = null): string;

    /**
     * Specification:
     * - Drops an existing SQLite Zed template and its generated migration, then builds it again.
     * - Builds the same way {@link \Spryker\Zed\Testify\Business\TestifyFacadeInterface::buildSqliteZedTemplate()} does.
     * - Target path defaults to {@link \Spryker\Zed\Testify\TestifyConfig::getSqliteZedTemplatePath()}.
     *
     * @api
     *
     * @param string|null $templatePath
     *
     * @throws \Spryker\Zed\Testify\Exception\SqliteZedTemplateBuildFailedException
     *
     * @return string
     */
    public function rebuildSqliteZedTemplate(?string $templatePath = null): string;
}
