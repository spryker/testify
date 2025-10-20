<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Shared\Testify\Helper;

use Codeception\Lib\Framework;
use Codeception\Lib\Interfaces\DependsOnModule;
use Codeception\Stub;
use Codeception\TestInterface;
use Spryker\Shared\Kernel\AbstractBundleConfig;
use Spryker\Zed\Testify\Bootstrap\ZedBootstrap as TestifyBootstrap;
use Spryker\Zed\Twig\TwigConfig;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\HttpKernel\HttpKernelBrowser;

class ZedBootstrap extends Framework implements DependsOnModule
{
    use ModuleHelperConfigTrait;

    /**
     * @var string
     */
    public const CONFIG_KEY_SERVICE_PROVIDER = 'serviceProvider';

    /**
     * @var string
     */
    public const CONFIG_KEY_APPLICATION_PLUGINS = 'applicationPlugins';

    /**
     * @var \Spryker\Zed\Testify\Bootstrap\ZedBootstrap
     */
    protected $application;

    /**
     * @var \SprykerTest\Shared\Testify\Helper\BundleConfig
     */
    protected $bundleConfig;

    /**
     * @return array<string, string>
     */
    public function _depends(): array
    {
        return [
            BundleConfig::class => sprintf('You need to enable `%s` in order to mock bundle configurations', BundleConfig::class),
        ];
    }

    /**
     * @param \SprykerTest\Shared\Testify\Helper\BundleConfig $bundleConfig
     *
     * @return void
     */
    public function _inject(BundleConfig $bundleConfig): void
    {
        $this->bundleConfig = $bundleConfig;
    }

    /**
     * @return void
     */
    public function _initialize(): void
    {
        $this->loadApplication();
        $this->mockBundleConfigs();
    }

    /**
     * @param \Codeception\TestInterface $test
     *
     * @return void
     */
    public function _before(TestInterface $test): void
    {
        if (class_exists(HttpKernelBrowser::class)) {
            $this->client = new HttpKernelBrowser($this->application->boot());

            return;
        }

        $this->client = new Client($this->application->boot());
    }

    /**
     * @return void
     */
    protected function setDefaultConfig(): void
    {
        $this->config = [
            static::CONFIG_KEY_SERVICE_PROVIDER => [],
            static::CONFIG_KEY_APPLICATION_PLUGINS => [],
        ];
    }

    /**
     * @return void
     */
    protected function loadApplication(): void
    {
        Request::setTrustedHosts(['localhost']);

        $requestFactory = function (array $query = [], array $request = [], array $attributes = [], array $cookies = [], array $files = [], array $server = [], $content = null) {
            $request = new Request($query, $request, $attributes, $cookies, $files, $server, $content);
            $request->server->set('SERVER_NAME', 'localhost');

            return $request;
        };
        Request::setFactory($requestFactory);

        $this->application = new TestifyBootstrap($this->config[static::CONFIG_KEY_APPLICATION_PLUGINS], $this->config[static::CONFIG_KEY_SERVICE_PROVIDER]);
    }

    /**
     * @return void
     */
    private function mockBundleConfigs(): void
    {
        $this->bundleConfig->addBundleConfigMock($this->getTwigBundleConfigMock());
    }

    /**
     * @return \Spryker\Shared\Kernel\AbstractBundleConfig
     */
    private function getTwigBundleConfigMock(): AbstractBundleConfig
    {
        $twigConfig = new TwigConfig();
        /** @var \Spryker\Shared\Kernel\AbstractBundleConfig $twigBundleConfigMock */
        $twigBundleConfigMock = Stub::make(TwigConfig::class, [
            'getTemplatePaths' => function () use ($twigConfig) {
                $paths = $twigConfig->getTemplatePaths();
                $paths[] = APPLICATION_ROOT_DIR . '/src/Spryker/%2$s/src/*/Zed/%1$s/Presentation';

                return $paths;
            },
        ]);

        return $twigBundleConfigMock;
    }
}
