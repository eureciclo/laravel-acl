<?php

namespace Signes\Acl;

use Mockery;
use PHPUnit\Framework\TestCase as PHPUnit_Framework_TestCase;


/**
 * Class TestCase
 */
class TestCase extends PHPUnit_Framework_TestCase
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
