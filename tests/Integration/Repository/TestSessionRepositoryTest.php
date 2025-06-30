<?php

namespace Tourze\TestPaperBundle\Tests\Integration\Repository;

use PHPUnit\Framework\TestCase;
use Tourze\TestPaperBundle\Repository\TestSessionRepository;

class TestSessionRepositoryTest extends TestCase
{
    public function testExtendsServiceEntityRepository(): void
    {
        $reflection = new \ReflectionClass(TestSessionRepository::class);
        $this->assertTrue($reflection->isSubclassOf(\Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository::class));
    }

    public function testRepositoryExists(): void
    {
        $this->assertTrue(class_exists(TestSessionRepository::class));
    }
}