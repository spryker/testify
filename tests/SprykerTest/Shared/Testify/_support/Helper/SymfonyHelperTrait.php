<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Shared\Testify\Helper;

use Codeception\Module;

trait SymfonyHelperTrait
{
    protected function getSymfonyHelper(): SymfonyHelper
    {
        /** @var \SprykerTest\Shared\Testify\Helper\SymfonyHelper $symfonyHelper */
        $symfonyHelper = $this->getModule('\\' . SymfonyHelper::class);

        return $symfonyHelper;
    }

    abstract protected function getModule(string $name): Module;
}
