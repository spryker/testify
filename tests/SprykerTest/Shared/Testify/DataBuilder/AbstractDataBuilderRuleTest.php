<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Shared\Testify\DataBuilder;

use Closure;
use Codeception\Test\Unit;
use Exception;
use Faker\Factory;
use ReflectionMethod;
use Spryker\Shared\Testify\AbstractDataBuilder;
use Spryker\Shared\Testify\Exception\InvalidRuleException;
use Spryker\Shared\Testify\TestifyConfig;
use Throwable;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Shared
 * @group Testify
 * @group DataBuilder
 * @group AbstractDataBuilderRuleTest
 * Add your own group annotations below this line
 */
class AbstractDataBuilderRuleTest extends Unit
{
    protected const string RULE_TRAILING_COMMA_SHORT_ARRAY = "randomElement(['a', 'b',])";

    protected const string RULE_TRAILING_COMMA_LONG_ARRAY = "randomElement(array('x', 'y',))";

    protected const string RULE_TRAILING_COMMA_ARGUMENT_LIST = 'numberBetween(1, 1,)';

    protected const string RULE_KEYED_SHORT_ARRAY = "randomElement(['k' => 'v'])";

    protected const string RULE_KEYED_LONG_ARRAY = "randomElement(array('k1' => 'v1', 'k2' => 'v1'))";

    protected const string RULE_MIXED_KEYED_AND_PLAIN_ARRAY = "randomElement([5 => 'five', 'six',])";

    protected const string RULE_ALLOWED_NESTED_FUNCTION = "date('Y-m-d', strtotime('-30 days'))";

    protected const string RULE_DISALLOWED_NESTED_FUNCTION = "date('Y-m-d', exec('id'))";

    protected const string RULE_NON_SCALAR_ARRAY_KEY = "randomElement([['a'] => 'v'])";

    protected const string RULE_KEYED_FUNCTION_ARGUMENT = 'numberBetween(1 => 2)';

    protected const string RULE_TEMPLATE_DISALLOWED_NESTED_FUNCTION = "date('Y-m-d', %s('id'))";

    protected const string RULE_TEMPLATE_FILE_WRITING_PAYLOAD = "date('Y-m-d', file_put_contents('%s', '%s'))";

    protected const string RULE_TOP_LEVEL_SYSTEM = "system('id')";

    protected const string RULE_TOP_LEVEL_FILE_GET_CONTENTS = "file_get_contents('/etc/passwd')";

    protected const string RULE_TOP_LEVEL_SHELL_EXEC = "shell_exec('id')";

    protected const string RULE_TOP_LEVEL_BACKTICK_COMMAND = '`id`';

    protected const string RULE_CORPUS_CALL_CHAIN = 'unique()->text(50)';

    protected const string RULE_CORPUS_METHOD_ON_RETURNED_OBJECT = "dateTime('-1 day')->format('Y-m-d H:i:s')";

    protected const string RULE_CORPUS_LONG_ARRAY_SYNTAX = "shuffle(array('DE', 'EN'))";

    protected const string RULE_CORPUS_SHORT_ARRAY_SYNTAX = "randomElement(['Mr', 'Mrs'])";

    protected const string RULE_CORPUS_BOOL_LITERAL_ARGUMENT = 'randomNumber(3, true)';

    protected const string RULE_CORPUS_STRING_LITERAL_WITH_COMMAS_AND_BRACES = "regexify('[a-z-]{5,15}\\.csv')";

    protected const string PATTERN_DATE = '/^\d{4}-\d{2}-\d{2}$/';

    protected const string PATTERN_DATE_TIME = '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/';

    protected const string PATTERN_CSV_FILE_NAME = '/^[a-z-]{5,15}\.csv$/';

    protected const string PAYLOAD_FILE_NAME_PREFIX = 'databuilder-payload-';

    protected const string PAYLOAD_FILE_CONTENT = 'pwned';

    protected const array VALUES_LONG_ARRAY_SYNTAX = ['DE', 'EN'];

    protected const array VALUES_SHORT_ARRAY_SYNTAX = ['Mr', 'Mrs'];

    /**
     * @return array<string, list<string>>
     */
    public static function disallowedFunctionInArgumentDataProvider(): array
    {
        return [
            'system' => ['system'],
            'shell_exec' => ['shell_exec'],
            'exec' => ['exec'],
            'passthru' => ['passthru'],
            'file_get_contents' => ['file_get_contents'],
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    public static function topLevelPayloadRuleDataProvider(): array
    {
        return [
            'command execution' => [static::RULE_TOP_LEVEL_SYSTEM],
            'file read' => [static::RULE_TOP_LEVEL_FILE_GET_CONTENTS],
            'shell execution' => [static::RULE_TOP_LEVEL_SHELL_EXEC],
            'backtick shell execution' => [static::RULE_TOP_LEVEL_BACKTICK_COMMAND],
        ];
    }

    /**
     * @return array<string, array{0: string, 1: \Closure}>
     */
    public static function corpusRuleShapeDataProvider(): array
    {
        return [
            'call chain on the unique generator' => [
                static::RULE_CORPUS_CALL_CHAIN,
                static function (self $test, mixed $value): void {
                    $test->assertIsString($value);
                    $test->assertNotSame('', $value);
                },
            ],
            'method call on the object returned by a formatter' => [
                static::RULE_CORPUS_METHOD_ON_RETURNED_OBJECT,
                static function (self $test, mixed $value): void {
                    $test->assertMatchesRegularExpression(static::PATTERN_DATE_TIME, $value);
                },
            ],
            'allow-listed function in argument position' => [
                static::RULE_ALLOWED_NESTED_FUNCTION,
                static function (self $test, mixed $value): void {
                    $test->assertMatchesRegularExpression(static::PATTERN_DATE, $value);
                },
            ],
            'long array syntax' => [
                static::RULE_CORPUS_LONG_ARRAY_SYNTAX,
                static function (self $test, mixed $value): void {
                    $test->assertEqualsCanonicalizing(static::VALUES_LONG_ARRAY_SYNTAX, $value);
                },
            ],
            'short array syntax' => [
                static::RULE_CORPUS_SHORT_ARRAY_SYNTAX,
                static function (self $test, mixed $value): void {
                    $test->assertContains($value, static::VALUES_SHORT_ARRAY_SYNTAX);
                },
            ],
            'bool literal argument' => [
                static::RULE_CORPUS_BOOL_LITERAL_ARGUMENT,
                static function (self $test, mixed $value): void {
                    $test->assertIsInt($value);
                },
            ],
            // A naive tokenizer splitting on "," or matching braces with a regular expression would
            // break this rule apart inside the string literal, so it is kept in the corpus table.
            'string literal containing commas, braces and escapes' => [
                static::RULE_CORPUS_STRING_LITERAL_WITH_COMMAS_AND_BRACES,
                static function (self $test, mixed $value): void {
                    $test->assertMatchesRegularExpression(static::PATTERN_CSV_FILE_NAME, $value);
                },
            ],
        ];
    }

    public function testTrailingCommaInShortArraySyntaxReturnsOneOfTheArrayElements(): void
    {
        // Act
        $value = $this->evaluateRule(static::RULE_TRAILING_COMMA_SHORT_ARRAY);

        // Assert
        $this->assertContains($value, ['a', 'b']);
    }

    public function testTrailingCommaInLongArraySyntaxReturnsOneOfTheArrayElements(): void
    {
        // Act
        $value = $this->evaluateRule(static::RULE_TRAILING_COMMA_LONG_ARRAY);

        // Assert
        $this->assertContains($value, ['x', 'y']);
    }

    public function testTrailingCommaInArgumentListPassesArgumentsToFakerMethod(): void
    {
        // Act
        $value = $this->evaluateRule(static::RULE_TRAILING_COMMA_ARGUMENT_LIST);

        // Assert
        $this->assertSame(1, $value);
    }

    public function testKeyedShortArraySyntaxReturnsTheArrayValue(): void
    {
        // Act
        $value = $this->evaluateRule(static::RULE_KEYED_SHORT_ARRAY);

        // Assert
        $this->assertSame('v', $value);
    }

    public function testKeyedLongArraySyntaxReturnsTheArrayValue(): void
    {
        // Act
        $value = $this->evaluateRule(static::RULE_KEYED_LONG_ARRAY);

        // Assert
        $this->assertSame('v1', $value);
    }

    public function testMixedKeyedAndPlainArrayElementsReturnsOneOfTheArrayValues(): void
    {
        // Act
        $value = $this->evaluateRule(static::RULE_MIXED_KEYED_AND_PLAIN_ARRAY);

        // Assert
        $this->assertContains($value, ['five', 'six']);
    }

    public function testAllowListedNestedFunctionCallIsExecuted(): void
    {
        // Act
        $value = $this->evaluateRule(static::RULE_ALLOWED_NESTED_FUNCTION);

        // Assert
        $this->assertIsString($value);
        $this->assertMatchesRegularExpression(static::PATTERN_DATE, $value);
    }

    public function testDisallowedNestedFunctionCallThrowsInvalidRuleException(): void
    {
        // Assert
        $this->expectException(InvalidRuleException::class);

        // Act
        $this->evaluateRule(static::RULE_DISALLOWED_NESTED_FUNCTION);
    }

    public function testNonScalarArrayKeyThrowsInvalidRuleException(): void
    {
        // Assert
        $this->expectException(InvalidRuleException::class);

        // Act
        $this->evaluateRule(static::RULE_NON_SCALAR_ARRAY_KEY);
    }

    public function testKeyedArgumentInFunctionArgumentListThrowsInvalidRuleException(): void
    {
        // Assert
        $this->expectException(InvalidRuleException::class);

        // Act
        $this->evaluateRule(static::RULE_KEYED_FUNCTION_ARGUMENT);
    }

    /**
     * @dataProvider disallowedFunctionInArgumentDataProvider
     */
    public function testDisallowedFunctionInArgumentThrowsInvalidRuleException(string $functionName): void
    {
        // Arrange
        $rule = sprintf(static::RULE_TEMPLATE_DISALLOWED_NESTED_FUNCTION, $functionName);

        // Assert
        $this->expectException(InvalidRuleException::class);

        // Act
        $this->evaluateRule($rule);
    }

    public function testDisallowedFileWritingFunctionInArgumentIsRejectedWithoutWritingTheFile(): void
    {
        // Arrange
        $payloadFilePath = $this->createPayloadFilePath();
        $this->assertFileDoesNotExist($payloadFilePath);

        // Act
        $throwable = $this->catchThrowableFromRule(sprintf(
            static::RULE_TEMPLATE_FILE_WRITING_PAYLOAD,
            $payloadFilePath,
            static::PAYLOAD_FILE_CONTENT,
        ));

        // Assert
        try {
            $this->assertInstanceOf(InvalidRuleException::class, $throwable);
            $this->assertFileDoesNotExist($payloadFilePath, 'The rejected rule must not have been executed at all.');
        } finally {
            $this->removePayloadFile($payloadFilePath);
        }
    }

    /**
     * The first identifier of a rule may only be a name Faker itself exposes as a formatter, so these
     * payloads never reach a PHP function lookup.
     *
     * @dataProvider topLevelPayloadRuleDataProvider
     */
    public function testTopLevelPayloadRuleIsRejectedAsUnknownFakerFormatter(string $rule): void
    {
        // Assert
        $this->expectException(InvalidRuleException::class);

        // Act
        $this->generateFromRule($rule);
    }

    /**
     * @dataProvider scalarChainSegmentRuleDataProvider
     */
    public function testMethodCallOnNonObjectChainSegmentThrowsInvalidRuleException(string $rule): void
    {
        // Assert
        $this->expectException(InvalidRuleException::class);

        // Act
        $this->evaluateRule($rule);
    }

    /**
     * @return array<string, list<string>>
     */
    public function scalarChainSegmentRuleDataProvider(): array
    {
        return [
            'method on a string result' => ['word()->x()'],
            'unknown method on a string result' => ['text(5)->nope()'],
        ];
    }

    /**
     * @dataProvider corpusRuleShapeDataProvider
     */
    public function testCorpusRuleProducesValueOfExpectedShape(string $rule, Closure $assertShape): void
    {
        // Act
        $value = $this->evaluateRule($rule);

        // Assert
        $assertShape($this, $value);
    }

    protected function evaluateRule(string $rule): mixed
    {
        return $this->invokeDataBuilderMethod('evaluateRule', $rule);
    }

    protected function generateFromRule(string $rule): mixed
    {
        return $this->invokeDataBuilderMethod('generateFromRule', $rule);
    }

    protected function invokeDataBuilderMethod(string $methodName, string $rule): mixed
    {
        $dataBuilder = $this->createDataBuilder();

        return (new ReflectionMethod($dataBuilder, $methodName))->invoke($dataBuilder, $rule);
    }

    protected function catchThrowableFromRule(string $rule): ?Throwable
    {
        try {
            $this->evaluateRule($rule);
        } catch (Throwable $throwable) {
            return $throwable;
        }

        return null;
    }

    protected function createPayloadFilePath(): string
    {
        return sprintf('%s/%s', sys_get_temp_dir(), uniqid(static::PAYLOAD_FILE_NAME_PREFIX, true));
    }

    protected function removePayloadFile(string $payloadFilePath): void
    {
        if (!is_file($payloadFilePath)) {
            return;
        }

        unlink($payloadFilePath);
    }

    protected function createDataBuilder(): AbstractDataBuilder
    {
        return new class extends AbstractDataBuilder
        {
            public function __construct()
            {
                static::$faker = Factory::create();
            }

            protected function getTransfer()
            {
                throw new Exception('Not used in this test');
            }

            /**
             * @param string $builder
             *
             * @throws \Exception
             */
            protected function locateDataBuilder($builder)
            {
                throw new Exception('Not used in this test');
            }

            protected function getSharedConfig(): TestifyConfig
            {
                return new class extends TestifyConfig
                {
                    public function isDataBuilderRuleEvalEnabled(): bool
                    {
                        return false;
                    }

                    /**
                     * @return list<string>
                     */
                    public function getDataBuilderAllowedRuleFunctions(): array
                    {
                        return ['strtotime'];
                    }
                };
            }
        };
    }
}
