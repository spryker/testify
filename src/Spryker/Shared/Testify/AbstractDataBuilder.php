<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Shared\Testify;

use Faker\Factory;
use InvalidArgumentException;
use Spryker\Shared\Kernel\Transfer\AbstractTransfer;
use Spryker\Shared\Testify\Exception\DependencyNotDefinedException;
use Spryker\Shared\Testify\Exception\FieldNotDefinedException;
use Spryker\Shared\Testify\Exception\InvalidRuleException;
use Spryker\Shared\Testify\Exception\RuleNotDefinedException;

abstract class AbstractDataBuilder
{
    /**
     * @var \Faker\Generator
     */
    protected static $faker;

    protected ?TestifyConfig $sharedConfig = null;

    /**
     * @var array<string, string>
     */
    protected $defaultRules = [];

    /**
     * @var array<string, string>
     */
    protected $rules = [];

    /**
     * @var array<string, string>
     */
    protected $dependencies = [];

    /**
     * @var array<mixed>
     */
    protected $nestedBuilders = [];

    /**
     * @var array<string, mixed>
     */
    protected $seedData = [];

    /**
     * @return \Spryker\Shared\Kernel\Transfer\AbstractTransfer
     */
    abstract protected function getTransfer();

    /**
     * @param string $builder
     *
     * @throws \Exception
     *
     * @return \Spryker\Shared\Testify\AbstractDataBuilder
     */
    abstract protected function locateDataBuilder($builder);

    /**
     * @param array<string, mixed> $seed
     */
    public function __construct($seed = [])
    {
        $this->seedData = $seed;
        $this->rules = $this->defaultRules;

        if (static::$faker === null) {
            static::$faker = Factory::create();
        }
    }

    /**
     * Removes all rules
     *
     * @return $this
     */
    public function makeEmpty()
    {
        $this->rules = [];

        return $this;
    }

    /**
     * @return $this
     */
    public function resetData()
    {
        $this->seedData = [];

        return $this;
    }

    /**
     * @param array<string, mixed> $seed
     *
     * @return $this
     */
    public function seed(array $seed = [])
    {
        $this->seedData += $seed;

        return $this;
    }

    /**
     * @param array<string>|string $rules
     *
     * @throws \Spryker\Shared\Testify\Exception\RuleNotDefinedException
     *
     * @return $this
     */
    public function with($rules)
    {
        if (!is_array($rules)) {
            $rules = [];
        }
        foreach ($rules as $rule) {
            if (!isset($this->defaultRules[$rule])) {
                throw new RuleNotDefinedException(sprintf('No rule for "%s" defined', $rule));
            }
            $this->rules[$rule] = $this->defaultRules[$rule];
        }

        return $this;
    }

    /**
     * @param array<string>|string $rules
     *
     * @return $this
     */
    public function except($rules)
    {
        if (!is_array($rules)) {
            $rules = [];
        }
        foreach ($rules as $rule) {
            unset($this->rules[$rule]);
        }

        return $this;
    }

    /**
     * @return \Spryker\Shared\Kernel\Transfer\AbstractTransfer
     */
    public function build()
    {
        $seedData = array_merge($this->generateFields(), $this->seedData);
        $transfer = $this->getTransfer()->fromArray($seedData, true);

        if ($this->nestedBuilders !== []) {
            $this->generateDependencies($transfer);
        }

        return $transfer;
    }

    /**
     * @return array<bool|string>
     */
    protected function generateFields()
    {
        $data = [];
        foreach ($this->rules as $field => $rule) {
            $data[$field] = $this->generateFromRule($rule);
        }

        return $data;
    }

    /**
     * @param string $field
     * @param array<string, mixed> $override
     * @param bool $randomize
     *
     * @throws \Spryker\Shared\Testify\Exception\FieldNotDefinedException
     *
     * @return void
     */
    protected function buildDependency($field, $override = [], $randomize = false)
    {
        if (!isset($this->dependencies[$field])) {
            throw new FieldNotDefinedException(sprintf('Field "%s" not defined in dependencies list', $field));
        }
        $builder = $this->locateDataBuilder($this->dependencies[$field]);
        $builder->seed($override);
        $this->addDependencyBuilder($field, $builder, $randomize);
    }

    /**
     * @param string $field
     * @param \Spryker\Shared\Testify\AbstractDataBuilder $builder
     * @param bool $randomize
     *
     * @return void
     */
    protected function addDependencyBuilder($field, $builder, $randomize)
    {
        $this->nestedBuilders[] = [$field, $builder, $randomize];
    }

    /**
     * @param \Spryker\Shared\Kernel\Transfer\AbstractTransfer $transfer
     *
     * @throws \Spryker\Shared\Testify\Exception\DependencyNotDefinedException
     *
     * @return void
     */
    protected function generateDependencies(AbstractTransfer $transfer)
    {
        foreach ($this->nestedBuilders as $builderInfo) {
            [$name, $dependencyBuilder, $randomize] = $builderInfo;

            if (!$randomize) {
                $parentSeedData = $this->seedData[$name] ?? [];
                $parentSeedData = $parentSeedData instanceof AbstractTransfer ? $parentSeedData->toArray() : $parentSeedData;
                $dependencySeedData = array_merge($dependencyBuilder->getSeedData(), $parentSeedData);

                $dependencyBuilder->seed($dependencySeedData);
            }

            $nestedTransfer = $dependencyBuilder->build();

            if (method_exists($transfer, 'add' . $name)) {
                /** @var callable $callable */
                $callable = [$transfer, 'add' . $name];
                call_user_func($callable, $nestedTransfer);

                continue;
            }

            if (method_exists($transfer, 'set' . $name)) {
                /** @var callable $callable */
                $callable = [$transfer, 'set' . $name];
                call_user_func($callable, $nestedTransfer);

                continue;
            }

            throw new DependencyNotDefinedException(sprintf('Dependency "%s" not defined in "%s"', $name, static::class));
        }
    }

    protected function generateFromRule(string $rule): mixed
    {
        if (strpos($rule, '=') === 0) {
            return substr($rule, 1);
        }

        if ($this->getSharedConfig()->isDataBuilderRuleEvalEnabled()) {
            return $this->generateFromRuleWithEval($rule);
        }

        if (strpos($rule, '(') === false) {
            return call_user_func($this->resolveFakerFormatter($rule));
        }

        return $this->evaluateRule($rule);
    }

    protected function getSharedConfig(): TestifyConfig
    {
        if ($this->sharedConfig === null) {
            $this->sharedConfig = new TestifyConfig();
        }

        return $this->sharedConfig;
    }

    /**
     * @SuppressWarnings("PHPMD.EvalExpression")
     */
    protected function generateFromRuleWithEval(string $rule): mixed
    {
        // @codingStandardsIgnoreStart
        if (strpos($rule, '(') !== false) {
            return eval("return static::\$faker->$rule;");
        }
        return eval("return static::\$faker->$rule();");
        // @codingStandardsIgnoreEnd
    }

    /**
     * @throws \Spryker\Shared\Testify\Exception\InvalidRuleException
     *
     * @return mixed
     */
    protected function evaluateRule(string $rule)
    {
        $tokens = $this->tokenizeRule($rule);
        $position = 0;

        $value = $this->evaluateFakerCallChain($tokens, $position);

        if ($position < count($tokens)) {
            throw new InvalidRuleException(sprintf('Unexpected trailing input in data builder rule "%s"', $rule));
        }

        return $value;
    }

    /**
     * @return list<array<int, int|string>|string>
     */
    protected function tokenizeRule(string $rule): array
    {
        $tokens = [];
        foreach (token_get_all(sprintf('<?php %s', $rule)) as $token) {
            if (is_array($token) && in_array($token[0], [T_OPEN_TAG, T_WHITESPACE], true)) {
                continue;
            }

            $tokens[] = $token;
        }

        return $tokens;
    }

    /**
     * @param list<array<int, int|string>|string> $tokens
     *
     * @return mixed
     */
    protected function evaluateFakerCallChain(array $tokens, int &$position)
    {
        $subject = null;
        $isChainRoot = true;

        while (true) {
            $methodName = $this->readIdentifier($tokens, $position);
            $arguments = $this->readArgumentList($tokens, $position);

            $callable = $isChainRoot
                ? $this->resolveFakerFormatter($methodName)
                : $this->resolveChainSegmentCallable($subject, $methodName);

            $subject = call_user_func_array($callable, $arguments);
            $isChainRoot = false;

            $token = $tokens[$position] ?? null;

            if (!is_array($token) || $token[0] !== T_OBJECT_OPERATOR) {
                break;
            }

            $position++;
        }

        return $subject;
    }

    protected function resolveFakerFormatter(string $name): callable
    {
        try {
            return static::$faker->getFormatter($name);
        } catch (InvalidArgumentException $invalidArgumentException) {
            throw new InvalidRuleException(
                sprintf('Formatter "%s" is not available in data builder rules', $name),
                0,
                $invalidArgumentException,
            );
        }
    }

    protected function resolveChainSegmentCallable(mixed $subject, string $name): callable
    {
        if (!is_object($subject) || !is_callable([$subject, $name])) {
            throw new InvalidRuleException(
                sprintf('Method "%s" cannot be called on the result of the previous rule segment', $name),
            );
        }

        return [$subject, $name];
    }

    /**
     * @param list<array<int, int|string>|string> $tokens
     *
     * @throws \Spryker\Shared\Testify\Exception\InvalidRuleException
     */
    protected function readIdentifier(array $tokens, int &$position): string
    {
        $token = $tokens[$position] ?? null;

        if (!is_array($token) || $token[0] !== T_STRING) {
            throw new InvalidRuleException('Expected an identifier in data builder rule');
        }

        $position++;

        return (string)$token[1];
    }

    /**
     * @param list<array<int, int|string>|string> $tokens
     *
     * @throws \Spryker\Shared\Testify\Exception\InvalidRuleException
     *
     * @return array<mixed>
     */
    protected function readArgumentList(array $tokens, int &$position): array
    {
        $this->consumeCharacter($tokens, $position, '(');

        $arguments = $this->readDelimitedList($tokens, $position, ')');

        if (!array_is_list($arguments)) {
            throw new InvalidRuleException('Keyed arguments are not supported in data builder rules');
        }

        return $arguments;
    }

    /**
     * @param list<array<int, int|string>|string> $tokens
     *
     * @return array<mixed>
     */
    protected function readDelimitedList(array $tokens, int &$position, string $closingCharacter): array
    {
        $values = [];

        if (($tokens[$position] ?? null) === $closingCharacter) {
            $position++;

            return $values;
        }

        while (true) {
            $this->readArrayElement($tokens, $position, $values);

            if (($tokens[$position] ?? null) === ',') {
                $position++;

                if (($tokens[$position] ?? null) === $closingCharacter) {
                    $position++;

                    break;
                }

                continue;
            }

            $this->consumeCharacter($tokens, $position, $closingCharacter);

            break;
        }

        return $values;
    }

    /**
     * @param list<array<int, int|string>|string> $tokens
     *
     * @throws \Spryker\Shared\Testify\Exception\InvalidRuleException
     *
     * @return mixed
     */
    protected function readArgument(array $tokens, int &$position)
    {
        $token = $tokens[$position] ?? null;

        if ($token === '-') {
            $position++;

            return -$this->readNumber($tokens, $position);
        }

        if (is_array($token) && $token[0] === T_CONSTANT_ENCAPSED_STRING) {
            $position++;

            return $this->decodeStringLiteral((string)$token[1]);
        }

        if (is_array($token) && ($token[0] === T_LNUMBER || $token[0] === T_DNUMBER)) {
            return $this->readNumber($tokens, $position);
        }

        if (is_array($token) && $token[0] === T_ARRAY) {
            $position++;
            $this->consumeCharacter($tokens, $position, '(');

            return $this->readDelimitedList($tokens, $position, ')');
        }

        if ($token === '[') {
            $position++;

            return $this->readDelimitedList($tokens, $position, ']');
        }

        if (is_array($token) && $token[0] === T_STRING) {
            return $this->readKeywordOrFunctionCall($tokens, $position);
        }

        throw new InvalidRuleException('Unexpected token in data builder rule argument');
    }

    /**
     * @param list<array<int, int|string>|string> $tokens
     *
     * @throws \Spryker\Shared\Testify\Exception\InvalidRuleException
     *
     * @return mixed
     */
    protected function readKeywordOrFunctionCall(array $tokens, int &$position)
    {
        $name = $this->readIdentifier($tokens, $position);
        $lowerCasedName = strtolower($name);

        if ($lowerCasedName === 'true') {
            return true;
        }

        if ($lowerCasedName === 'false') {
            return false;
        }

        if ($lowerCasedName === 'null') {
            return null;
        }

        if (!in_array($lowerCasedName, $this->getSharedConfig()->getDataBuilderAllowedRuleFunctions(), true) || !is_callable($lowerCasedName)) {
            throw new InvalidRuleException(sprintf('Function "%s" is not allowed in data builder rules', $name));
        }

        $arguments = $this->readArgumentList($tokens, $position);

        return call_user_func_array($lowerCasedName, $arguments);
    }

    /**
     * @param list<array<int, int|string>|string> $tokens
     * @param array<mixed> $values
     *
     * @throws \Spryker\Shared\Testify\Exception\InvalidRuleException
     */
    protected function readArrayElement(array $tokens, int &$position, array &$values): void
    {
        $value = $this->readArgument($tokens, $position);

        $token = $tokens[$position] ?? null;

        if (!is_array($token) || $token[0] !== T_DOUBLE_ARROW) {
            $values[] = $value;

            return;
        }

        if (!is_int($value) && !is_string($value)) {
            throw new InvalidRuleException('Array key in a data builder rule must be an integer or a string');
        }

        $position++;
        $values[$value] = $this->readArgument($tokens, $position);
    }

    /**
     * @param list<array<int, int|string>|string> $tokens
     *
     * @throws \Spryker\Shared\Testify\Exception\InvalidRuleException
     *
     * @return float|int
     */
    protected function readNumber(array $tokens, int &$position)
    {
        $token = $tokens[$position] ?? null;

        if (!is_array($token) || ($token[0] !== T_LNUMBER && $token[0] !== T_DNUMBER)) {
            throw new InvalidRuleException('Expected a number in data builder rule');
        }

        $position++;

        return $token[0] === T_LNUMBER ? (int)$token[1] : (float)$token[1];
    }

    protected function decodeStringLiteral(string $literal): string
    {
        $quoteCharacter = $literal[0];
        $body = substr($literal, 1, -1);

        if ($quoteCharacter === "'") {
            return str_replace(['\\\\', "\\'"], ['\\', "'"], $body);
        }

        return stripcslashes($body);
    }

    /**
     * @param list<array<int, int|string>|string> $tokens
     *
     * @throws \Spryker\Shared\Testify\Exception\InvalidRuleException
     */
    protected function consumeCharacter(array $tokens, int &$position, string $character): void
    {
        if (($tokens[$position] ?? null) !== $character) {
            throw new InvalidRuleException(sprintf('Expected "%s" in data builder rule', $character));
        }

        $position++;
    }

    /**
     * @return array<string, mixed>
     */
    public function getSeedData()
    {
        return $this->seedData;
    }
}
