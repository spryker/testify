<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerTest\Shared\Testify\Helper;

use Codeception\Module\Symfony as SymfonyModule;
use Codeception\TestInterface;
use Spryker\Shared\Application\Kernel;
use Spryker\Shared\Kernel\Container\ContainerProxy;
use SprykerTest\Shared\Testify\Helper\Kernel\TestKernel;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Codeception helper extending Symfony module with custom kernel support.
 *
 * This helper supports two modes:
 * - **Project Mode** (default): Full kernel boot with project integration
 * - **Core Mode**: Isolated testing without project code (Spryker internal use)
 *
 * Configuration example (Project Mode - default):
 * ```yaml
 * modules:
 *     enabled:
 *         - \SprykerTest\Shared\Testify\Helper\SymfonyHelper:
 *             mode: 'project'
 *             environment: 'test'
 * ```
 *
 * Configuration example (Core Mode with bundle configuration):
 * ```yaml
 * modules:
 *     enabled:
 *         - \SprykerTest\Shared\Testify\Helper\SymfonyHelper:
 *             mode: 'core'
 *             environment: 'test'
 *             bundles:
 *                 - Spryker\ApiPlatform\SprykerApiPlatformBundle
 *                 - Symfony\Bundle\FrameworkBundle\FrameworkBundle
 *             bundle_configurations:
 *                 spryker_api_platform:
 *                     source_directories:
 *                         - src/Spryker/ApiPlatform/resources/api
 *                     cache_dir: '%kernel.cache_dir%/api-generator'
 *                     generated_dir: '%kernel.project_dir%/src/Generated/Api'
 *                     debug: true
 * ```
 */
class SymfonyHelper extends SymfonyModule
{
    /**
     * @var array<string, mixed>
     */
    public array $config = [
        'mode' => 'project',
        'environment' => 'test',
        'debug' => true,
        'app_path' => 'src',
        'kernel_class' => null,
        'cache_router' => false,
        'rebootable_client' => true,
        'bundles' => [],
        'bundle_configurations' => [],
    ];

    /**
     * @var array<string, object>
     */
    protected array $originalServices = [];

    public function _initialize(): void
    {
        if ($this->config['mode'] === 'core') {
            $this->config['kernel_class'] = TestKernel::class;
            $this->config['app_path'] = '';

            return;
        }

        if ($this->config['kernel_class'] === null) {
            $this->config['kernel_class'] = new Kernel(new ContainerProxy([
                'debug' => true,
                'charset' => 'UTF-8',
                'canUseDi' => true,
            ]));
        }

        parent::_initialize();
    }

    public function addBundleConfiguration(string $bundleName, array $configuration): void
    {
        $this->config['bundle_configurations'][$bundleName] = $configuration;
    }

    public function _before(TestInterface $test): void
    {
        $this->originalServices = [];

        if ($this->config['mode'] === 'core') {
            $this->getKernel();
        }

        parent::_before($test);
    }

    protected function getKernel(): KernelInterface
    {
        if ($this->config['mode'] === 'core') {
            $kernel = new TestKernel(new ContainerProxy(['test' => true]));

            if (!empty($this->config['bundles'])) {
                $kernel->addBundles($this->config['bundles']);
            }

            if (!empty($this->config['bundle_configurations'])) {
                $kernel->addBundleConfigurations($this->config['bundle_configurations']);
            }

            $kernel->boot();

            $this->kernel = $kernel;

            return $this->kernel;
        }

        $kernelClass = $this->config['kernel_class'];
        $this->kernel = new $kernelClass($this->config['environment'], $this->config['debug']);
        $this->kernel->boot();

        return $this->kernel;
    }

    public function getService(string $id): object
    {
        return $this->getContainer()->get($id);
    }

    public function setService(string $id, object $service): void
    {
        $container = $this->getContainer();

        if ($container->has($id) && !array_key_exists($id, $this->originalServices)) {
            $this->originalServices[$id] = $container->get($id);
        }

        $container->set($id, $service);
    }

    public function hasService(string $id): bool
    {
        return $this->getContainer()->has($id);
    }

    public function getContainer(): ContainerInterface
    {
        return $this->_getContainer();
    }
}
