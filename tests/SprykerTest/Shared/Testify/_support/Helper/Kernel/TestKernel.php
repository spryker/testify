<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerTest\Shared\Testify\Helper\Kernel;

use Spryker\Service\Container\ContainerDelegator;
use Spryker\Shared\Application\Kernel as BaseKernel;
use SprykerTest\ApiPlatform\Test\TestModeConfiguration;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Minimal Symfony Kernel for isolated testing in core mode.
 *
 * This kernel is designed for testing modules without project dependencies.
 * It accepts a list of bundle class names and boots only those bundles
 * with minimal framework configuration.
 *
 * Example usage:
 * ```php
 * $kernel = new TestKernel(
 *     bundles: [
 *         \Spryker\ApiPlatform\SprykerApiPlatformBundle::class,
 *         \Symfony\Bundle\FrameworkBundle\FrameworkBundle::class,
 *     ],
 *     environment: 'test'
 * );
 * $kernel->boot();
 * ```
 */
class TestKernel extends BaseKernel
{
    /**
     * @var array<class-string>
     */
    protected array $bundleClasses = [];

    /**
     * @var array<string, array<string, mixed>>
     */
    protected array $bundleConfigurations = [];

    /**
     * Ensure that Symfony gets the compiled container.
     */
    public function getContainer(): ContainerInterface
    {
        return ContainerDelegator::getInstance()->getContainer('project_container');
    }

    public function addBundles(array $bundles): self
    {
        $this->bundleClasses = $bundles;

        return $this;
    }

    /**
     * @param array<string, array<string, mixed>> $configurations
     */
    public function addBundleConfigurations(array $configurations): self
    {
        $this->bundleConfigurations = $configurations;

        return $this;
    }

    /**
     * Register bundles provided via constructor.
     *
     * @return iterable<\Symfony\Component\HttpKernel\Bundle\BundleInterface>
     */
    public function registerBundles(): iterable
    {
        foreach ($this->bundleClasses as $bundleClass) {
            yield new $bundleClass();
        }
    }

    /**
     * Provide minimal container configuration.
     *
     * @param \Symfony\Component\Config\Loader\LoaderInterface $loader
     *
     * @return void
     */
    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        if (TestModeConfiguration::isProjectMode()) {
            parent::registerContainerConfiguration($loader);

            return;
        }

        $loader->load(function (ContainerBuilder $container): void {
            $this->configureTestContainer($container);
        });
    }

    protected function configureTestContainer(ContainerBuilder $container): void
    {
        $container->setParameter('kernel.project_dir', $this->getProjectDir());

        $container->loadFromExtension('framework', [
            'secret' => 'test_secret',
            'test' => true,
            'router' => [
                'utf8' => true,
                'resource' => 'kernel::loadRoutes',
                'type' => 'service',
            ],
            'http_method_override' => false,
        ]);

        foreach ($this->bundleConfigurations as $bundleName => $configuration) {
            $container->loadFromExtension($bundleName, $configuration);
        }

        $container->setParameter('kernel.bundles', $this->bundleClasses);
    }

    public static function getCacheDirPath(string $moduleRoot): string
    {
        return sprintf('%s/tests/_data/symfony_test_kernel_cache/', $moduleRoot);
    }

    public function getCacheDir(): string
    {
        if (TestModeConfiguration::isProjectMode()) {
            return parent::getCacheDir();
        }

        return static::getCacheDirPath($this->getProjectDir());
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir();
    }

    public function getProjectDir(): string
    {
        return codecept_data_dir();
    }
}
