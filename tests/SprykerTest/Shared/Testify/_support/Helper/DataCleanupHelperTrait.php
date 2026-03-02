<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Shared\Testify\Helper;

use Codeception\Module;

trait DataCleanupHelperTrait
{
    protected function getDataCleanupHelper(): DataCleanupHelper
    {
        if (method_exists($this, 'hasModule') && !$this->hasModule('\\' . DataCleanupHelper::class)) {
            $this->moduleContainer->create('\\' . DataCleanupHelper::class);
        }

        /** @var \SprykerTest\Shared\Testify\Helper\DataCleanupHelper $dataCleanerHelper */
        $dataCleanerHelper = $this->getModule('\\' . DataCleanupHelper::class);

        return $dataCleanerHelper;
    }

    abstract protected function getModule(string $name): Module;
}
