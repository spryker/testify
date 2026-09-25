<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerTest\Zed\Testify\Communication\Console;

use Codeception\Test\Unit;
use Spryker\Zed\Testify\Business\Sqlite\SqliteZedTemplateBuilderInterface;
use Spryker\Zed\Testify\Communication\Console\BuildSqliteTemplateConsole;
use Spryker\Zed\Testify\Exception\SqliteZedTemplateBuildFailedException;
use SprykerTest\Zed\Testify\TestifyCommunicationTester;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group Testify
 * @group Communication
 * @group Console
 * @group BuildSqliteTemplateConsoleTest
 * Add your own group annotations below this line
 */
class BuildSqliteTemplateConsoleTest extends Unit
{
    protected const string TEMPLATE_PATH = '/tmp/build-sqlite-template-console-test.sqlite';

    protected TestifyCommunicationTester $tester;

    public function testGivenTheBuilderSucceedsWhenTheCommandRunsThenItReportsWhatWasDoneAndExitsZero(): void
    {
        // Arrange
        $builderMock = $this->createBuilderMock();
        $builderMock->method('build')->willReturn('Template built.');
        $commandTester = $this->createCommandTester($builderMock);

        // Act
        $commandTester->execute([]);

        // Assert
        $this->assertSame(BuildSqliteTemplateConsole::CODE_SUCCESS, $commandTester->getStatusCode());
        $this->assertStringContainsString('Template built.', $commandTester->getDisplay());
    }

    public function testGivenTheBuilderFailsWhenTheCommandRunsThenItReportsTheReasonAndExitsNonZero(): void
    {
        // Arrange
        $builderMock = $this->createBuilderMock();
        $builderMock->method('build')
            ->willThrowException(new SqliteZedTemplateBuildFailedException('propel:migrate failed.'));
        $commandTester = $this->createCommandTester($builderMock);

        // Act
        $commandTester->execute([]);

        // Assert
        $this->assertSame(BuildSqliteTemplateConsole::CODE_ERROR, $commandTester->getStatusCode());
        $this->assertStringContainsString('propel:migrate failed.', $commandTester->getDisplay());
    }

    public function testGivenThePathOptionWhenTheCommandRunsThenItReachesTheBuilder(): void
    {
        // Arrange
        $builderMock = $this->createBuilderMock();
        $builderMock->expects($this->once())
            ->method('build')
            ->with(static::TEMPLATE_PATH)
            ->willReturn('Template built.');
        $commandTester = $this->createCommandTester($builderMock);

        // Act
        $commandTester->execute(['--path' => static::TEMPLATE_PATH]);

        // Assert
        $this->assertSame(BuildSqliteTemplateConsole::CODE_SUCCESS, $commandTester->getStatusCode());
    }

    public function testGivenTheForceOptionWhenTheCommandRunsThenTheTemplateIsRebuiltRatherThanSkipped(): void
    {
        // Arrange
        $builderMock = $this->createBuilderMock();
        $builderMock->expects($this->never())->method('build');
        $builderMock->expects($this->once())
            ->method('rebuild')
            ->with(static::TEMPLATE_PATH)
            ->willReturn('Template rebuilt.');
        $commandTester = $this->createCommandTester($builderMock);

        // Act
        $commandTester->execute(['--path' => static::TEMPLATE_PATH, '--force' => true]);

        // Assert
        $this->assertSame(BuildSqliteTemplateConsole::CODE_SUCCESS, $commandTester->getStatusCode());
        $this->assertStringContainsString('Template rebuilt.', $commandTester->getDisplay());
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|\Spryker\Zed\Testify\Business\Sqlite\SqliteZedTemplateBuilderInterface
     */
    protected function createBuilderMock(): SqliteZedTemplateBuilderInterface
    {
        return $this->createMock(SqliteZedTemplateBuilderInterface::class);
    }

    /**
     * @param \PHPUnit\Framework\MockObject\MockObject|\Spryker\Zed\Testify\Business\Sqlite\SqliteZedTemplateBuilderInterface $builderMock
     */
    protected function createCommandTester(SqliteZedTemplateBuilderInterface $builderMock): CommandTester
    {
        $this->tester->mockFactoryMethod('createSqliteZedTemplateBuilder', $builderMock);

        $command = new BuildSqliteTemplateConsole();
        $command->setFacade($this->tester->getFacade());

        return $this->tester->getConsoleTester($command);
    }
}
