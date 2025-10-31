<?php

declare(strict_types=1);

namespace TestPaperBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;
use Tourze\TestPaperBundle\TestPaperBundle;

/**
 * @internal
 */
#[CoversClass(TestPaperBundle::class)]
#[RunTestsInSeparateProcesses]
final class TestPaperBundleTest extends AbstractBundleTestCase
{
}
