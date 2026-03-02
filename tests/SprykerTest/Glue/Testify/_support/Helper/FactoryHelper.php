<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Glue\Testify\Helper;

use Codeception\Configuration;
use Codeception\Module;
use Codeception\Stub;
use Codeception\TestInterface;
use Exception;
use Spryker\Glue\Kernel\AbstractBundleConfig;
use Spryker\Glue\Kernel\AbstractFactory;
use Spryker\Glue\Kernel\Container;
use SprykerTest\Shared\Testify\Helper\ConfigHelper;
use SprykerTest\Shared\Testify\Helper\ConfigHelperTrait;
use SprykerTest\Shared\Testify\Helper\ModuleNameTrait;
use SprykerTest\Shared\Testify\Helper\SourceNamespaceTrait;

class FactoryHelper extends Module
{
    use DependencyProviderHelperTrait;
    use ConfigHelperTrait;
    use ModuleNameTrait;
    use SourceNamespaceTrait;

    /**
     * @var string
     */
    protected const FACTORY_CLASS_NAME_PATTERN = '\%1$s\Glue\%2$s\%2$sFactory';

    /**
     * @var array<\Spryker\Glue\Kernel\AbstractFactory>
     */
    protected $factoryStubs = [];

    /**
     * @var array
     */
    protected $mockedFactoryMethods = [];

    /**
     * @param string $methodName
     * @param mixed $return
     * @param string|null $moduleName
     *
     * @throws \Exception
     *
     * @return \Spryker\Glue\Kernel\AbstractFactory
     */
    public function mockFactoryMethod(string $methodName, $return, ?string $moduleName = null): AbstractFactory
    {
        $moduleName = $this->getModuleName($moduleName);
        $className = $this->getFactoryClassName($moduleName);

        if (!method_exists($className, $methodName)) {
            throw new Exception(sprintf('You tried to mock a not existing method "%s". Available methods are "%s"', $methodName, implode(', ', get_class_methods($className))));
        }

        if (!isset($this->mockedFactoryMethods[$moduleName])) {
            $this->mockedFactoryMethods[$moduleName] = [];
        }

        $this->mockedFactoryMethods[$moduleName][$methodName] = $return;
        /** @var \Spryker\Glue\Kernel\AbstractFactory $factoryStub */
        $factoryStub = Stub::make($className, $this->mockedFactoryMethods[$moduleName]);
        $this->factoryStubs[$moduleName] = $factoryStub;

        return $this->factoryStubs[$moduleName];
    }

    public function getFactory(?string $moduleName = null): AbstractFactory
    {
        $moduleName = $this->getModuleName($moduleName);

        if (isset($this->factoryStubs[$moduleName])) {
            $this->factoryStubs[$moduleName] = $this->injectConfig($this->factoryStubs[$moduleName], $moduleName);
            $this->factoryStubs[$moduleName] = $this->injectContainer($this->factoryStubs[$moduleName], $moduleName);

            return $this->factoryStubs[$moduleName];
        }

        $moduleFactory = $this->createFactory($moduleName);

        $moduleFactory = $this->injectConfig($moduleFactory, $moduleName);
        $moduleFactory = $this->injectContainer($moduleFactory, $moduleName);

        return $moduleFactory;
    }

    protected function createFactory(?string $moduleName = null): AbstractFactory
    {
        $moduleName = $this->getModuleName($moduleName);
        $moduleFactoryClassName = $this->getFactoryClassName($moduleName);

        return new $moduleFactoryClassName();
    }

    protected function getFactoryClassName(string $moduleName): string
    {
        $config = Configuration::config();
        $namespaceParts = explode('\\', $config['namespace']);

        return sprintf(static::FACTORY_CLASS_NAME_PATTERN, $this->removeTestSuffix($namespaceParts[0]), $moduleName);
    }

    protected function injectConfig(AbstractFactory $factory, string $moduleName): AbstractFactory
    {
        if ($this->hasModule('\\' . ConfigHelper::class)) {
            $factory->setConfig($this->getConfig($moduleName));
        }

        return $factory;
    }

    protected function getConfig(string $moduleName): AbstractBundleConfig
    {
        /** @var \Spryker\Glue\Kernel\AbstractBundleConfig $moduleConfig */
        $moduleConfig = $this->getConfigHelper()->getModuleConfig($moduleName);

        return $moduleConfig;
    }

    protected function injectContainer(AbstractFactory $factory, string $moduleName): AbstractFactory
    {
        if ($this->hasModule('\\' . DependencyProviderHelper::class)) {
            $factory->setContainer($this->getContainer($moduleName));
        }

        return $factory;
    }

    protected function getContainer(string $moduleName): Container
    {
        return $this->getDependencyProviderHelper()->getModuleContainer($moduleName);
    }

    public function _before(TestInterface $test): void
    {
        $this->factoryStubs = [];
        $this->mockedFactoryMethods = [];
    }
}
