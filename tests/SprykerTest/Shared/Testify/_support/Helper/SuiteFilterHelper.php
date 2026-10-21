<?php
// phpcs:ignoreFile
// setSuteTests() reaches into PHPUnit's private TestSuite::$tests by reflection, so its
// $testSuite parameter cannot carry a native type and the docblock sniffs cannot be satisfied.

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Shared\Testify\Helper;

use Codeception\Event\SuiteEvent;
use Codeception\Events;
use Codeception\TestInterface;
use ReflectionClass;
use Codeception\Extension;

class SuiteFilterHelper extends Extension
{
    /**
     * @var string
     */
    protected const CONFIG_KEY_INCLUSIVE = 'inclusive';

    /**
     * @var string
     */
    protected const CONFIG_KEY_EXCLUSIVE = 'exclusive';

    /**
     * @var array<string>
     */
    public static $events = [
        'suite.start' => 'filterSuiteByGroupsCodeception5',
    ];

    /**
     * @param \Codeception\Event\SuiteEvent $e
     *
     * @return void
     */
    public function filterSuiteByGroupsCodeception5(SuiteEvent $e): void
    {
        $testSuite = $e->getSuite();
        $tests = [];

        foreach ($testSuite->getTests() as $test) {
            if ($test instanceof TestInterface) {
                $groups = $test->getMetadata()->getGroups();

                if ($this->isTestAllowedbyGroups($groups)) {
                    $tests[] = $test;
                }
            }
        }

        $this->setSuteTests($testSuite, $tests);
    }

    /**
     * @return array<string>
     */
    protected function getInclusiveGroups(): array
    {
        return $this->getUniqueConfigByKey(static::CONFIG_KEY_INCLUSIVE);
    }

    /**
     * @return array<string>
     */
    protected function getExclusiveGroups(): array
    {
        return $this->getUniqueConfigByKey(static::CONFIG_KEY_EXCLUSIVE);
    }

    /**
     * @param string $configKey
     *
     * @return array<string>
     */
    protected function getUniqueConfigByKey(string $configKey): array
    {
        if (array_key_exists($configKey, $this->config)) {
            return array_unique($this->config[$configKey]);
        }

        return [];
    }

    /**
     * @param $testSuite
     * @param array $tests
     * @return void
     * @throws \ReflectionException
     */
    protected function setSuteTests($testSuite, array $tests)
    {
        $refClass = new ReflectionClass($testSuite);
        $reflectionProperty = $refClass->getProperty('tests');
        $reflectionProperty->setValue($testSuite, $tests);
    }

    /**
     * @param array $groups
     * @return bool
     */
    public function isTestAllowedbyGroups(array $groups): bool
    {
        $inclusiveGroups = $this->getInclusiveGroups();
        $exclusiveGroups = $this->getExclusiveGroups();

        if ($inclusiveGroups !== [] && count(array_intersect($groups, $inclusiveGroups)) !== count($inclusiveGroups)) {
            return false;
        }

        if ($exclusiveGroups !== [] && count(array_intersect($groups, $exclusiveGroups)) > 0) {
            return false;
        }

        return true;
    }
}
