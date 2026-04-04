<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Shared\Testify\Helper;

use Codeception\Event\TestEvent;
use Codeception\Events;
use Codeception\Extension;

/**
 * Prevents PHP's internal "headers sent" flag from leaking between tests when all
 * suites run in a single process.
 *
 * When session_start() is called during a test, PHP sends the Set-Cookie header and
 * permanently sets SG(headers_sent) = true for the process lifetime. Subsequent tests
 * that call session_set_cookie_params() or session_set_save_handler() then fail with
 * "cannot be changed after headers have already been sent".
 *
 * Wrapping every test in ob_start() / ob_end_clean() keeps any session cookie in the
 * output buffer so it is never flushed to stdout, leaving headers_sent() = false for
 * the next test — regardless of whether that test uses SessionHelper or not.
 */
class SessionStateResetterExtension extends Extension
{
    /**
     * Positive priority fires before the Module subscriber (priority 0) that calls _before() hooks.
     * Negative priority fires after the Module subscriber that calls _after() hooks.
     *
     * @var array<string, string|array<int, int|string>>
     */
    public static array $events = [
        Events::TEST_BEFORE => ['beforeTest', 100],
        Events::TEST_AFTER => ['afterTest', -100],
    ];

    public function beforeTest(TestEvent $event): void
    {
        ob_start();
    }

    public function afterTest(TestEvent $event): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        if (ob_get_level() > 0) {
            ob_end_clean();
        }

        // Clear any pending Set-Cookie or other headers accumulated during the test.
        // Without this, PHP sends them when Codeception writes test output between tests,
        // permanently setting SG(headers_sent)=1 and breaking subsequent session calls.
        if (!headers_sent()) {
            header_remove();
        }
    }
}
