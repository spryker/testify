<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace Spryker\Zed\Testify\Business\Sqlite;

interface SqliteZedTemplateBuilderInterface
{
    /**
     * Builds the template unless one is already there.
     *
     * @param string|null $templatePath Absolute target path; the configured default when null.
     *
     * @throws \Spryker\Zed\Testify\Exception\SqliteZedTemplateBuildFailedException
     *
     * @return string What was done, for the caller to render.
     */
    public function build(?string $templatePath): string;

    /**
     * Drops an existing template and its generated migration, then builds it again.
     *
     * @param string|null $templatePath Absolute target path; the configured default when null.
     *
     * @throws \Spryker\Zed\Testify\Exception\SqliteZedTemplateBuildFailedException
     *
     * @return string What was done, for the caller to render.
     */
    public function rebuild(?string $templatePath): string;
}
