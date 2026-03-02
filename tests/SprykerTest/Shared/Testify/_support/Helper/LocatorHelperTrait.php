<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Shared\Testify\Helper;

use Codeception\Module;

trait LocatorHelperTrait
{
    /**
     * @return \Generated\Service\Ide\AutoCompletion&\Generated\Zed\Ide\AutoCompletion&\Spryker\Shared\Kernel\LocatorLocatorInterface
     */
    protected function getLocator()
    {
        return $this->getLocatorHelper()->getLocator();
    }

    protected function getLocatorHelper(): LocatorHelper
    {
        /** @var \SprykerTest\Shared\Testify\Helper\LocatorHelper $locatorHelper */
        $locatorHelper = $this->getModule('\\' . LocatorHelper::class);

        return $locatorHelper;
    }

    abstract protected function getModule(string $name): Module;
}
