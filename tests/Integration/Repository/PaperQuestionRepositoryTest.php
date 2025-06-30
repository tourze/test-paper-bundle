<?php

namespace Tourze\TestPaperBundle\Tests\Integration\Repository;

use PHPUnit\Framework\TestCase;
use Tourze\TestPaperBundle\Repository\PaperQuestionRepository;

class PaperQuestionRepositoryTest extends TestCase
{
    public function testExtendsServiceEntityRepository(): void
    {
        $reflection = new \ReflectionClass(PaperQuestionRepository::class);
        $this->assertTrue($reflection->isSubclassOf(\Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository::class));
    }

    public function testRepositoryExists(): void
    {
        $this->assertTrue(class_exists(PaperQuestionRepository::class));
    }
}