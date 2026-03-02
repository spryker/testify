<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Shared\Testify\Helper;

use Closure;
use Codeception\Configuration;
use Codeception\Module;
use Codeception\Stub;
use Codeception\TestInterface;
use Exception;
use Spryker\Shared\Testify\Locator\TestifyConfiguratorInterface;
use Spryker\Zed\Kernel\AbstractBundleConfig;
use Spryker\Zed\Kernel\Business\AbstractBusinessFactory;
use Spryker\Zed\Kernel\Business\AbstractFacade;
use Spryker\Zed\Testify\Locator\Business\BusinessLocator as Locator;

/**
 * @deprecated Use {@link \SprykerTest\Zed\Testify\Helper\BusinessHelper} instead.
 */
class BusinessHelper extends Module
{
    use SourceNamespaceTrait;

    /**
     * @var string
     */
    protected const BUSINESS_CLASS_NAME_PATTERN = '\%1$s\%2$s\%3$s\Business\%3$sBusinessFactory';

    /**
     * @var array
     */
    protected $dependencies = [];

    /**
     * @var \Spryker\Zed\Kernel\Business\AbstractBusinessFactory|null
     */
    protected $factoryStub;

    /**
     * @var array
     */
    protected $mockedFactoryMethods = [];

    /**
     * @return \Spryker\Shared\Kernel\LocatorLocatorInterface&\Generated\Zed\Ide\AutoCompletion|\Generated\Service\Ide\AutoCompletion
     */
    public function getLocator()
    {
        return new Locator();
    }

    /**
     * @param string $key
     * @param mixed $value
     *
     * @return $this
     */
    public function setDependency(string $key, $value)
    {
        $this->dependencies[$key] = $value;

        return $this;
    }

    public function getFacade(): AbstractFacade
    {
        $facade = $this->createFacade();
        $facade->setFactory($this->getFactory());

        return $facade;
    }

    protected function createFacade(): AbstractFacade
    {
        $currentNamespace = Configuration::config()['namespace'];
        $namespaceParts = explode('\\', $currentNamespace);
        $moduleName = lcfirst($namespaceParts[2]);

        return $this->getLocator()->$moduleName()->facade($this->createClosure());
    }

    /**
     * @param string $methodName
     * @param mixed $return
     *
     * @throws \Exception
     *
     * @return \Spryker\Zed\Kernel\Business\AbstractBusinessFactory|object
     */
    public function mockFactoryMethod(string $methodName, $return)
    {
        $className = $this->getFactoryClassName();

        if (!method_exists($className, $methodName)) {
            throw new Exception(sprintf('You tried to mock a not existing method "%s". Available methods are "%s"', $methodName, implode(', ', get_class_methods($className))));
        }

        $this->mockedFactoryMethods[$methodName] = $return;
        $this->factoryStub = Stub::make($className, $this->mockedFactoryMethods);

        return $this->factoryStub;
    }

    public function getFactory(): AbstractBusinessFactory
    {
        if ($this->factoryStub !== null) {
            return $this->factoryStub;
        }

        $moduleFactory = $this->createModuleFactory();
        if ($this->hasModule('\\' . ConfigHelper::class)) {
            $moduleFactory->setConfig($this->getConfig());
        }

        return $moduleFactory;
    }

    protected function createModuleFactory(): AbstractBusinessFactory
    {
        $moduleFactoryClassName = $this->getFactoryClassName();

        return new $moduleFactoryClassName();
    }

    protected function getFactoryClassName(): string
    {
        $config = Configuration::config();
        $namespaceParts = explode('\\', $config['namespace']);

        return sprintf(static::BUSINESS_CLASS_NAME_PATTERN, $this->removeTestSuffix($namespaceParts[0]), $namespaceParts[1], $namespaceParts[2]);
    }

    protected function getConfig(): AbstractBundleConfig
    {
        return $this->getConfigHelper()->getModuleConfig();
    }

    protected function getConfigHelper(): ConfigHelper
    {
        return $this->getModule('\\' . ConfigHelper::class);
    }

    private function createClosure(): Closure
    {
        $dependencies = $this->getDependencies();
        $callback = function (TestifyConfiguratorInterface $configurator) use ($dependencies): void {
            foreach ($dependencies as $key => $value) {
                $configurator->getContainer()->set($key, $value);
            }
        };

        return $callback;
    }

    private function getDependencies(): array
    {
        $dependencies = $this->dependencies;
        $this->dependencies = [];

        return $dependencies;
    }

    public function _before(TestInterface $test): void
    {
        $this->factoryStub = null;
        $this->mockedFactoryMethods = [];
    }
}
