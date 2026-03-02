<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Service\Testify\Helper;

use Codeception\Module;

trait ServiceHelperTrait
{
    protected function getServiceHelper(): ServiceHelper
    {
        /** @var \SprykerTest\Service\Testify\Helper\ServiceHelper $serviceHelper */
        $serviceHelper = $this->getModule('\\' . ServiceHelper::class);

        return $serviceHelper;
    }

    abstract protected function getModule(string $name): Module;
}
