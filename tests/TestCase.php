<?php

namespace Signes\Acl;

use Mockery;
use PHPUnit\Framework\TestCase as FrameworkTestCase;

/**
 * Class TestCase
 */
class TestCase extends FrameworkTestCase
{
    /**
     * Tear down environment after tests
     */
    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
