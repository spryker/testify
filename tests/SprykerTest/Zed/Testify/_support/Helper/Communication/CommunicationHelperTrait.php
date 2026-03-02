<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\Testify\Helper\Communication;

use Codeception\Module;

trait CommunicationHelperTrait
{
    protected function getCommunicationHelper(): CommunicationHelper
    {
        /** @var \SprykerTest\Zed\Testify\Helper\Communication\CommunicationHelper $factoryHelper */
        $factoryHelper = $this->getModule('\\' . CommunicationHelper::class);

        return $factoryHelper;
    }

    abstract protected function getModule(string $name): Module;
}
