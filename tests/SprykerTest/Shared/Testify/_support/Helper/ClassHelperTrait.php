<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Shared\Testify\Helper;

use Codeception\Module;

trait ClassHelperTrait
{
    protected function getClassHelper(): ClassHelper
    {
        /** @var \SprykerTest\Shared\Testify\Helper\ClassHelper $classHelper */
        $classHelper = $this->getModule('\\' . ClassHelper::class);

        return $classHelper;
    }

    abstract protected function getModule(string $name): Module;
}
