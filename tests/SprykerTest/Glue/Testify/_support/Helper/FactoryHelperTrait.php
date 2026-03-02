<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Glue\Testify\Helper;

use Codeception\Module;

trait FactoryHelperTrait
{
    protected function getFactoryHelper(): FactoryHelper
    {
        /** @var \SprykerTest\Glue\Testify\Helper\FactoryHelper $factoryHelper */
        $factoryHelper = $this->getModule('\\' . FactoryHelper::class);

        return $factoryHelper;
    }

    abstract protected function getModule(string $name): Module;
}
