<?php

namespace Tourze\TestPaperBundle\Tests\Integration\Service;

use PHPUnit\Framework\TestCase;
use Tourze\TestPaperBundle\Service\TestSessionService;

class TestSessionServiceTest extends TestCase
{
    public function testServiceExists(): void
    {
        $this->assertTrue(class_exists(TestSessionService::class));
    }

    public function testCanBeInstantiated(): void
    {
        $reflection = new \ReflectionClass(TestSessionService::class);
        $this->assertTrue($reflection->isInstantiable());
    }
}