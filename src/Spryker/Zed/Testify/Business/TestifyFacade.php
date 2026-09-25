<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Testify\Business;

use Spryker\Zed\Kernel\Business\AbstractFacade;

/**
 * @method \Spryker\Zed\Testify\Business\TestifyBusinessFactory getFactory()
 */
class TestifyFacade extends AbstractFacade implements TestifyFacadeInterface
{
    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @return array<string>
     */
    public function cleanUpOutputDirectories(): array
    {
        return $this->getFactory()->createOutputCleaner()->cleanup();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param string|null $templatePath
     *
     * @return string
     */
    public function buildSqliteZedTemplate(?string $templatePath = null): string
    {
        return $this->getFactory()->createSqliteZedTemplateBuilder()->build($templatePath);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param string|null $templatePath
     *
     * @return string
     */
    public function rebuildSqliteZedTemplate(?string $templatePath = null): string
    {
        return $this->getFactory()->createSqliteZedTemplateBuilder()->rebuild($templatePath);
    }
}
