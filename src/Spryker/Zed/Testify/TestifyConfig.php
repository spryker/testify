<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Testify;

use Spryker\Zed\Kernel\AbstractBundleConfig;

class TestifyConfig extends AbstractBundleConfig
{
    /**
     * @api
     *
     * @return array<string>
     */
    public function getOutputDirectoriesForCleanup(): array
    {
        $directories = [
            APPLICATION_ROOT_DIR . '/tests/_output/',
            APPLICATION_ROOT_DIR . '/tests/PyzTest/*/*/_output/',
            rtrim(APPLICATION_SOURCE_DIR, '/') . '/Spryker/*/tests/_output/',
            rtrim(APPLICATION_SOURCE_DIR, '/') . '/Spryker/*/tests/SprykerTest/*/*/_output/',
            rtrim(APPLICATION_SOURCE_DIR, '/') . '/SprykerFeature/*/tests/_output/',
            rtrim(APPLICATION_SOURCE_DIR, '/') . '/SprykerFeature/*/tests/SprykerTest/*/*/_output/',
            rtrim(APPLICATION_SOURCE_DIR, '/') . '/SprykerShop/*/tests/SprykerTest/*/*/_output/',
        ];

        $directories = array_filter($directories, 'glob');

        return $directories;
    }

    /**
     * @api
     *
     * @return string
     */
    public function getApplicationRootDir(): string
    {
        return APPLICATION_ROOT_DIR;
    }

    /**
     * @api
     *
     * @return string
     */
    public function getSqliteZedTemplatePath(): string
    {
        return APPLICATION_ROOT_DIR . '/data/spike-zed.sqlite';
    }

    /**
     * The propel diff for the SQLite engine is generated into its own directory, so it never
     * collides with the migrations of the engine the project actually deploys on.
     *
     * @api
     *
     * @return string
     */
    public function getSqliteZedTemplateMigrationDirectory(): string
    {
        return APPLICATION_ROOT_DIR . '/src/Orm/Propel/Migration_sqlite';
    }
}
