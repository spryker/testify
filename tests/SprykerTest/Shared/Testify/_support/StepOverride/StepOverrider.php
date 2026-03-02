<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Shared\Testify\StepOverride;

use Codeception\Scenario;
use Codeception\Step;
use Codeception\Step\Meta;
use Codeception\Test\Metadata;
use ReflectionClass;
use ReflectionObject;

class StepOverrider extends Scenario
{
    /**
     * @var \Codeception\Scenario
     */
    protected $scenario;

    /**
     * @var string
     */
    protected $stepDescription;

    /**
     * @var callable
     */
    protected $releaseHook;

    /**
     * @var array<string>
     */
    protected $prepositions = [];

    public function __construct(Scenario $scenario, string $stepDescription, callable $releaseHook)
    {
        $this->scenario = $scenario;
        $this->stepDescription = $stepDescription;
        $this->releaseHook = $releaseHook;

        // TODO [E2E] Consider other way to override steps
        $scenarioReflection = new ReflectionObject($scenario);
        $testProperty = $scenarioReflection->getProperty('test');
        $testProperty->setAccessible(true);
        parent::__construct($testProperty->getValue($scenario));
    }

    /**
     * @see \Codeception\Scenario::addStep()
     *
     * @param \Codeception\Step $step
     *
     * @return mixed
     */
    public function runStep(Step $step): mixed
    {
        $className = __NAMESPACE__ . '\\' . basename((new ReflectionClass($step))->getShortName()) . 'Extender';

        if (class_exists($className)) {
            $step = new $className($step->getAction(), $step->getArguments());
        }

        if ($step instanceof StepDescriptionExtender) {
            $step = $step->setStepDescription($this->stepDescription . $this->getPreposition());
        }

        ($this->releaseHook)();

        return $this->scenario->runStep($step);
    }

    public function setFeature(string $feature): void
    {
        $this->scenario->setFeature($feature);
    }

    public function getFeature(): string
    {
        return $this->scenario->getFeature();
    }

    /**
     * @inheritDoc
     */
    public function getGroups(): array
    {
        return $this->scenario->getGroups();
    }

    /**
     * @inheritDoc
     */
    public function current(?string $key)
    {
        return $this->scenario->current($key);
    }

    public function addStep(Step $step): void
    {
        $this->scenario->addStep($step);
    }

    /**
     * @inheritDoc
     */
    public function getSteps(): array
    {
        return $this->scenario->getSteps();
    }

    public function getHtml(): string
    {
        return $this->scenario->getHtml();
    }

    public function getText(): string
    {
        return $this->scenario->getText();
    }

    public function comment(string $comment): void
    {
        $this->scenario->comment($comment);
    }

    public function skip(string $message = ''): void
    {
        $this->scenario->skip($message);
    }

    public function incomplete(string $message = ''): void
    {
        $this->scenario->incomplete($message);
    }

    public function setMetaStep(?Meta $metaStep): void
    {
        $this->scenario->setMetaStep($metaStep);
    }

    public function getMetaStep(): ?Meta
    {
        return $this->scenario->getMetaStep();
    }

    protected function getPreposition(): string
    {
        return count($this->prepositions) > 0 ? ' ' . trim(implode(' ', $this->prepositions)) . ' ' : '';
    }

    /**
     * @param string $preposition
     *
     * @return static
     */
    public function addPreposition(string $preposition): self
    {
        $this->prepositions[] = trim($preposition);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getMetadata()
    {
        return new Metadata();
    }

    /**
     * @param string $name
     * @param array $arguments
     *
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->scenario, $name], $arguments);
    }
}
