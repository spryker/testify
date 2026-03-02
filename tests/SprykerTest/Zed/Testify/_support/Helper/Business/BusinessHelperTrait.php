<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\Testify\Helper\Business;

use Codeception\Module;

trait BusinessHelperTrait
{
    protected function getBusinessHelper(): BusinessHelper
    {
        /** @var \SprykerTest\Zed\Testify\Helper\Business\BusinessHelper $businessHelper */
        $businessHelper = $this->getModule('\\' . BusinessHelper::class);

        return $businessHelper;
    }

    abstract protected function getModule(string $name): Module;
}
