<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerTest\Shared\Testify\Helper;

use Codeception\Module;

/**
 * Codeception helper for bootstrapping test environment with application plugins.
 *
 * This helper allows configuring application plugin providers via codeception.yml
 * to avoid hardcoding factory instantiation in test cases.
 *
 * Configuration example:
 * ```yaml
 * modules:
 *     enabled:
 *         - \SprykerTest\Shared\Testify\Helper\BootstrapHelper:
 *             applicationPluginProvider:
 *                 class: Spryker\Glue\GlueBackendApiApplication\GlueBackendApiApplicationFactory
 *                 method: getApplicationPlugins
 * ```
 */
class BootstrapHelper extends Module
{
    /**
     * @var array<string, mixed>
     */
    public array $config = [
        'applicationPluginProvider' => null,
    ];

    protected static ?BootstrapHelper $instance = null;

    public function _initialize(): void
    {
        static::$instance = $this;

        parent::_initialize();
    }

    /**
     * Returns application plugins from the configured factory.
     *
     * If no applicationPluginProvider is configured, returns an empty array.
     *
     * @return array<\Spryker\Service\Container\ContainerInterface>
     */
    public static function getApplicationPlugins(): array
    {
        if (static::$instance === null) {
            return [];
        }

        $providerConfig = static::$instance->config['applicationPluginProvider'];

        if ($providerConfig === null) {
            return [];
        }

        $factoryClass = $providerConfig['class'];
        $factoryMethod = $providerConfig['method'];

        $factory = new $factoryClass();

        return $factory->$factoryMethod();
    }
}
