<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\Testify\Helper\Persistence;

use Codeception\Module;

trait FactoryHelperTrait
{
    protected function getFactoryHelper(): FactoryHelper
    {
        /** @var \SprykerTest\Zed\Testify\Helper\Persistence\FactoryHelper $factoryHelper */
        $factoryHelper = $this->getModule('\\' . FactoryHelper::class);

        return $factoryHelper;
    }

    abstract protected function getModule(string $name): Module;
}
