<?php

/**
 * This file is part of the Phalcon Incubator Test.
 *
 * (c) Phalcon Team <team@phalcon.io>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace KlbV2\Core\Test\PHPUnit;

use KlbV2\Core\Test\Traits\UnitTestCase as UnitTestCaseTrait;
use PHPUnit\Framework\TestCase as TestCase;

abstract class UnitTestCase extends TestCase
{
    use UnitTestCaseTrait;

    protected function setUp(): void
    {
        $this->setUpPhalcon();
    }

    protected function tearDown(): void
    {
        $this->tearDownPhalcon();

        parent::tearDown();
    }
}
