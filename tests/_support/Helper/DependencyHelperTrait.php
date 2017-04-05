<?php

/**
 * Copyright © 2017-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Testify\Helper;

trait DependencyHelperTrait
{

    /**
     * @param string $key
     * @param mixed $value
     *
     * @return void
     */
    private function setDependency($key, $value)
    {
        $this->getDependencyHelper()->setDependency($key, $value);
    }

    /**
     * @return \Codeception\Module|\Testify\Helper\Dependency
     */
    private function getDependencyHelper()
    {
        return $this->getModule('\\' . Dependency::class);
    }

    /**
     * @param string $name
     *
     * @return \Codeception\Module
     */
    abstract protected function getModule($name);

}