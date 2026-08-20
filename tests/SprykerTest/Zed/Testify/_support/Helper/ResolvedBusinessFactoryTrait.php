<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerTest\Zed\Testify\Helper;

use Spryker\Zed\Kernel\ClassResolver\Business\BusinessFactoryResolver;

/**
 * Names the business factory a dependency override has to be scoped to.
 *
 * Scope matters twice over: an unscoped binding is handed to every module that happens to spell a
 * dependency key the same way — `CLIENT_STORAGE_REDIS` alone is claimed by several — and the name
 * has to be the factory the locator actually builds, which is the project override wherever there
 * is one.
 */
trait ResolvedBusinessFactoryTrait
{
    /**
     * @var array<string, class-string>
     */
    protected array $resolvedBusinessFactoryClassNames = [];

    /**
     * @param class-string $facadeClassName
     *
     * @return class-string
     */
    protected function getBusinessFactoryClassNameFor(string $facadeClassName): string
    {
        if (!isset($this->resolvedBusinessFactoryClassNames[$facadeClassName])) {
            $this->resolvedBusinessFactoryClassNames[$facadeClassName] = (new BusinessFactoryResolver())
                ->resolve($facadeClassName)::class;
        }

        return $this->resolvedBusinessFactoryClassNames[$facadeClassName];
    }
}
