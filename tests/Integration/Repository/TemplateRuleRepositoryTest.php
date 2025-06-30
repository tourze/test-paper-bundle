<?php

namespace Tourze\TestPaperBundle\Tests\Integration\Repository;

use PHPUnit\Framework\TestCase;
use Tourze\TestPaperBundle\Repository\TemplateRuleRepository;

class TemplateRuleRepositoryTest extends TestCase
{
    public function testExtendsServiceEntityRepository(): void
    {
        $reflection = new \ReflectionClass(TemplateRuleRepository::class);
        $this->assertTrue($reflection->isSubclassOf(\Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository::class));
    }

    public function testRepositoryExists(): void
    {
        $this->assertTrue(class_exists(TemplateRuleRepository::class));
    }
}