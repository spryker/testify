<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\Testify\Helper\Business;

use Codeception\Module;

trait DependencyProviderHelperTrait
{
    /**
     * @return \SprykerTest\Zed\Testify\Helper\Business\DependencyProviderHelper
     */
    protected function getDependencyProviderHelper(): DependencyProviderHelper
    {
        /** @var \SprykerTest\Zed\Testify\Helper\Business\DependencyProviderHelper $dependencyProviderHelper */
        $dependencyProviderHelper = $this->getModule('\\' . DependencyProviderHelper::class);

        return $dependencyProviderHelper;
    }

    /**
     * @param string $name
     *
     * @return \Codeception\Module
     */
    abstract protected function getModule(string $name): Module;
}
