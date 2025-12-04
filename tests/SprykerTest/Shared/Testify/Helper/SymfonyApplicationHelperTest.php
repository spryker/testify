<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerTest\Shared\Testify\Helper;

use Codeception\Lib\ModuleContainer;
use Codeception\Test\Unit;
use Codeception\TestInterface;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\HttpKernelBrowser;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Shared
 * @group Testify
 * @group Helper
 * @group SymfonyApplicationHelperTest
 * Add your own group annotations below this line
 */
class SymfonyApplicationHelperTest extends Unit
{
    public function testGivenCoreModeWhenBootingThenRegistersOnlyConfiguredBundles(): void
    {
        // Arrange
        $helper = $this->createHelperWithConfig([
            'mode' => 'core',
            'bundles' => [FrameworkBundle::class],
        ]);

        // Act
        $container = $helper->getContainer();
        $bundles = $container->getParameter('kernel.bundles');

        // Assert
        $this->assertIsArray($bundles);
        $this->assertCount(1, $bundles);
        $this->assertContains(FrameworkBundle::class, $bundles);
    }

    public function testGivenServiceExistsWhenGettingThenReturnsService(): void
    {
        // Arrange
        $helper = $this->createHelperWithConfig([
            'mode' => 'core',
            'bundles' => [FrameworkBundle::class],
        ]);

        // Act
        $service = $helper->getService('kernel');

        // Assert
        $this->assertInstanceOf(KernelInterface::class, $service);
    }

    public function testGivenServiceNotExistsWhenGettingThenThrowsException(): void
    {
        // Arrange
        $helper = $this->createHelperWithConfig([
            'mode' => 'core',
            'bundles' => [FrameworkBundle::class],
        ]);

        // Expect
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Service "non_existent_service" not found');

        // Act
        $helper->getService('non_existent_service');
    }

    public function testGivenServiceWhenCheckingExistenceThenReturnsCorrectResult(): void
    {
        // Arrange
        $helper = $this->createHelperWithConfig([
            'mode' => 'core',
            'bundles' => [FrameworkBundle::class],
        ]);

        // Act & Assert
        $this->assertTrue($helper->hasService('kernel'));
        $this->assertFalse($helper->hasService('non_existent_service'));
    }

    public function testGivenHelperWhenCreatingClientThenReturnsKernelBrowser(): void
    {
        // Arrange
        $helper = $this->createHelperWithConfig([
            'mode' => 'core',
            'bundles' => [FrameworkBundle::class],
        ]);

        // Act
        $client = $helper->createClient();

        // Assert
        $this->assertInstanceOf(HttpKernelBrowser::class, $client);
    }

    public function testGivenBootedKernelWhenTestEndsTheNextTestGetsCleanContainer(): void
    {
        // Arrange
        $helper = $this->createHelperWithConfig([
            'mode' => 'core',
            'bundles' => [FrameworkBundle::class],
        ]);

        $kernel1 = $helper->getContainer()->get('kernel');

        // Act
        $helper->_before($this->createTestDouble());
        $kernel2 = $helper->getContainer()->get('kernel');

        // Assert
        $this->assertNotSame($kernel1, $kernel2);
    }

    public function testGivenContainerWhenGettingThenReturnsContainerInterface(): void
    {
        // Arrange
        $helper = $this->createHelperWithConfig([
            'mode' => 'core',
            'bundles' => [FrameworkBundle::class],
        ]);

        // Act
        $container = $helper->getContainer();

        // Assert
        $this->assertInstanceOf(ContainerInterface::class, $container);
    }

    protected function createHelperWithConfig(array $config): SymfonyApplicationHelper
    {
        $helper = new SymfonyApplicationHelper(
            $this->makeEmpty(ModuleContainer::class),
            $config,
        );

        return $helper;
    }

    protected function createTestDouble(): TestInterface
    {
        return $this->makeEmpty(TestInterface::class);
    }
}
