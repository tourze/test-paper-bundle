<?php

namespace Tourze\TestPaperBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\BundleDependency\BundleDependencyInterface;
use Tourze\DoctrineTimestampBundle\DoctrineTimestampBundle;
use Tourze\QuestionBankBundle\QuestionBankBundle;

class TestPaperBundle extends Bundle implements BundleDependencyInterface
{
    public static function getBundleDependencies(): array
    {
        return [
            DoctrineTimestampBundle::class => ['all' => true],
            QuestionBankBundle::class => ['all' => true],
        ];
    }
}
