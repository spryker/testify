<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Shared\Testify\Helper;

use ArrayObject;
use Codeception\Configuration;
use Codeception\Module;
use Codeception\TestInterface;
use ReflectionClass;
use ReflectionProperty;
use Spryker\Shared\Config\Config;
use Spryker\Shared\Kernel\AbstractLocatorLocator;
use Spryker\Shared\Kernel\BundleProxy;
use Spryker\Shared\Kernel\ClassResolver\AbstractClassResolver;
use Spryker\Shared\Kernel\KernelConstants;
use Spryker\Zed\Kernel\BundleDependencyProviderResolverAwareTrait;
use Spryker\Zed\Kernel\Business\AbstractFacade;
use Spryker\Zed\Testify\Locator\Business\BusinessLocator;

class LocatorHelper extends Module
{
    use ModuleHelperConfigTrait;

    /**
     * @var array
     */
    protected $configCache;

    public function _initialize(): void
    {
        Config::init();
        $reflectionProperty = $this->getConfigReflectionProperty();
        $this->configCache = $reflectionProperty->getValue()->getArrayCopy();
    }

    protected function setDefaultConfig(): void
    {
        $this->config = [
            'projectNamespaces' => [],
            'coreNamespaces' => [
                'SprykerShop',
                'Spryker',
                'SprykerEco',
                'SprykerSdk',
                'SprykerFeature',
            ],
        ];
    }

    /**
     * Sets a class instance into the Locator cache to ensure the mocked instance is returned when
     * `$locator->moduleName()->type()` is used.
     *
     * !!! When this method is used the locator will not re-initialize classes with `new` but will return
     * always the already resolved instances. This can have but should not have side-effects.
     *
     * @param string $cacheKey
     * @param mixed $classInstance
     *
     * @return void
     */
    public function addToLocatorCache(string $cacheKey, $classInstance): void
    {
        $bundleProxyInstanceCachePropertyReflection = new ReflectionProperty(BundleProxy::class, 'instanceCache');
        $bundleProxyInstanceCachePropertyReflection->setAccessible(true);
        $instanceCache = $bundleProxyInstanceCachePropertyReflection->getValue();
        $instanceCache[$cacheKey] = [
            'instance' => $classInstance,
            'className' => get_class($classInstance),
        ];
        $bundleProxyInstanceCachePropertyReflection->setValue(null, $instanceCache);

        $bundleProxyIsInstanceCacheEnabledPropertyReflection = new ReflectionProperty(BundleProxy::class, 'isInstanceCacheEnabled');
        $bundleProxyIsInstanceCacheEnabledPropertyReflection->setAccessible(true);
        $bundleProxyIsInstanceCacheEnabledPropertyReflection->setValue(null, true);
    }

    protected function getConfigReflectionProperty(): ReflectionProperty
    {
        $reflection = new ReflectionClass(Config::class);
        $reflectionProperty = $reflection->getProperty('config');
        $reflectionProperty->setAccessible(true);

        return $reflectionProperty;
    }

    /**
     * @param string $key
     * @param array|string|float|int|bool $value
     *
     * @return void
     */
    public function setConfig(string $key, $value): void
    {
        $configProperty = $this->getConfigReflectionProperty();
        $config = $configProperty->getValue();
        $config[$key] = $value;
        $configProperty->setValue(null, $config);
    }

    public function isProjectNamespaceEnabled(): bool
    {
        return $this->config['projectNamespaces'] !== [];
    }

    /**
     * @param array $settings
     *
     * @return void
     */
    public function _beforeSuite($settings = []): void
    {
        $this->clearLocators();
        $this->clearContainers();
        $this->clearCaches();
        $this->configureNamespacesForClassResolver();
    }

    public function _before(TestInterface $test): void
    {
        $this->clearLocators();
        $this->clearContainers();
        $this->clearCaches();
        $this->configureNamespacesForClassResolver();
    }

    protected function clearLocators(): void
    {
        $reflection = new ReflectionClass(AbstractLocatorLocator::class);
        $instanceProperty = $reflection->getProperty('instance');
        $instanceProperty->setAccessible(true);
        $instanceProperty->setValue(null, null);
    }

    protected function clearContainers(): void
    {
        $reflection = new ReflectionClass(BundleDependencyProviderResolverAwareTrait::class);
        $instanceProperty = $reflection->getProperty('containers');
        $instanceProperty->setAccessible(true);
        $instanceProperty->setValue(null, null);
    }

    protected function clearCaches(): void
    {
        $abstractClassResolverReflection = new ReflectionClass(AbstractClassResolver::class);

        if ($abstractClassResolverReflection->hasProperty('cache')) {
            $instanceProperty = $abstractClassResolverReflection->getProperty('cache');
            $instanceProperty->setAccessible(true);
            $instanceProperty->setValue(null, []);
        }

        $bundleProxyReflection = new ReflectionClass(BundleProxy::class);

        if ($bundleProxyReflection->hasProperty('instanceCache')) {
            $instanceProperty = $bundleProxyReflection->getProperty('instanceCache');
            $instanceProperty->setAccessible(true);
            $instanceProperty->setValue(null, []);
        }
    }

    private function configureNamespacesForClassResolver(): void
    {
        $this->setConfig(KernelConstants::PROJECT_NAMESPACES, $this->config['projectNamespaces']);
        $this->setConfig(KernelConstants::CORE_NAMESPACES, $this->config['coreNamespaces']);
    }

    /**
     * @return \Spryker\Shared\Kernel\LocatorLocatorInterface&\Generated\Zed\Ide\AutoCompletion&\Generated\Service\Ide\AutoCompletion&\Generated\Glue\Ide\AutoCompletion
     */
    public function getLocator()
    {
        return new BusinessLocator();
    }

    /**
     * @deprecated Use {@link \SprykerTest\Zed\Testify\Helper\Business\BusinessHelper::getFacade()} instead.
     *
     * @return \Spryker\Zed\Kernel\Business\AbstractFacade
     */
    public function getFacade(): AbstractFacade
    {
        $currentNamespace = Configuration::config()['namespace'];
        $namespaceParts = explode('\\', $currentNamespace);
        $bundleName = lcfirst(end($namespaceParts));

        return $this->getLocator()->$bundleName()->facade();
    }

    public function _after(TestInterface $test): void
    {
        $this->clearLocators();
        $this->clearCaches();
        $this->resetConfig();
    }

    private function resetConfig(): void
    {
        $reflectionProperty = $this->getConfigReflectionProperty();
        $reflectionProperty->setValue(null, new ArrayObject($this->configCache));
    }
}
