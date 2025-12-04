<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerTest\Shared\Testify\Helper\Kernel;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\RouteCollection;

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
     * @param string $environment The environment name (e.g., 'test')
     * @param bool $debug Whether to enable debug mode
     */
    public function __construct(
        string $environment = 'test',
        bool $debug = true,
    ) {
        parent::__construct($environment, $debug);
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
        $loader->load(function (ContainerBuilder $container): void {
            $this->configureContainer($container);
        });
    }

    /**
     * Configure container with minimal settings.
     *
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     *
     * @return void
     */
    protected function configureContainer(ContainerBuilder $container): void
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

    public function loadRoutes($loader)
    {
        return new RouteCollection();
    }

    /**
     * Use temporary directory for cache.
     *
     * @return string
     */
    public function getCacheDir(): string
    {
        return sys_get_temp_dir() . '/symfony_test_kernel_cache/' . $this->environment;
    }

    /**
     * Use temporary directory for logs.
     *
     * @return string
     */
    public function getLogDir(): string
    {
        return sys_get_temp_dir() . '/symfony_test_kernel_logs';
    }

    /**
     * Project directory for test kernel.
     *
     * Uses codecept root directory if available, otherwise falls back to temporary directory.
     *
     * @return string
     */
    public function getProjectDir(): string
    {
        if (function_exists('codecept_root_dir')) {
            return codecept_root_dir();
        }

        return sys_get_temp_dir() . '/symfony_test_kernel';
    }
}
