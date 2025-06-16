<?php

namespace Tourze\TestPaperBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\BundleDependency\BundleDependencyInterface;
use Tourze\DoctrineIndexedBundle\DoctrineIndexedBundle;
use Tourze\DoctrineSnowflakeBundle\DoctrineSnowflakeBundle;
use Tourze\DoctrineTimestampBundle\DoctrineTimestampBundle;
use Tourze\DoctrineUserBundle\DoctrineUserBundle;
use Tourze\QuestionBankBundle\QuestionBankBundle;
use Tourze\TestPaperBundle\DependencyInjection\TestPaperExtension;

class TestPaperBundle extends Bundle implements BundleDependencyInterface
{
    public static function getBundleDependencies(): array
    {
        return [
            DoctrineTimestampBundle::class => ['all' => true],
            DoctrineIndexedBundle::class => ['all' => true],
            DoctrineSnowflakeBundle::class => ['all' => true],
            DoctrineUserBundle::class => ['all' => true],
            QuestionBankBundle::class => ['all' => true],
        ];
    }

    public function getContainerExtension(): ?TestPaperExtension
    {
        return new TestPaperExtension();
    }
}
