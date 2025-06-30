<?php

namespace Tourze\TestPaperBundle\Tests\Integration\Repository;

use PHPUnit\Framework\TestCase;
use Tourze\TestPaperBundle\Repository\PaperTemplateRepository;

class PaperTemplateRepositoryTest extends TestCase
{
    public function testExtendsServiceEntityRepository(): void
    {
        $reflection = new \ReflectionClass(PaperTemplateRepository::class);
        $this->assertTrue($reflection->isSubclassOf(\Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository::class));
    }

    public function testRepositoryExists(): void
    {
        $this->assertTrue(class_exists(PaperTemplateRepository::class));
    }
}