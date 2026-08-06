<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Shared\Testify;

use Spryker\Shared\Kernel\AbstractSharedConfig;

class TestifyConfig extends AbstractSharedConfig
{
    /**
     * @api
     *
     * @return bool
     */
    public function isLocatorInstanceCacheEnabled(): bool
    {
        return false;
    }

    /**
     * Specification:
     * - Defines whether a data builder rule is executed as PHP code with `eval()`.
     * - Enabled by default, so a rule may contain any PHP expression.
     * - When disabled, rules are parsed instead: only Faker formatters, literal arguments and the
     *   functions returned by {@link static::getDataBuilderAllowedRuleFunctions()} are accepted, and
     *   anything else is rejected with `\Spryker\Shared\Testify\Exception\InvalidRuleException`.
     *
     * @api
     *
     * @return bool
     */
    public function isDataBuilderRuleEvalEnabled(): bool
    {
        return (bool)$this->get(TestifyConstants::IS_DATA_BUILDER_RULE_EVAL_ENABLED, true);
    }

    /**
     * Specification:
     * - Returns the list of PHP functions that are allowed in parsed data builder rules.
     * - Only applies while rules are parsed, so it has no effect when rule `eval()` is enabled.
     *
     * @api
     *
     * @return list<string>
     */
    public function getDataBuilderAllowedRuleFunctions(): array
    {
        return $this->get(TestifyConstants::DATA_BUILDER_ALLOWED_RULE_FUNCTIONS, [
            'strtotime',
        ]);
    }
}
