<?php

namespace Tourze\TestPaperBundle\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\TestPaperBundle\TestPaperBundle;

class TestPaperBundleTest extends TestCase
{
    private TestPaperBundle $bundle;

    protected function setUp(): void
    {
        $this->bundle = new TestPaperBundle();
    }

    public function testInstanceOfBundle(): void
    {
        $this->assertInstanceOf(\Symfony\Component\HttpKernel\Bundle\Bundle::class, $this->bundle);
    }

    public function testBuild(): void
    {
        $container = new ContainerBuilder();
        $this->bundle->build($container);
        
        // 如果没有异常抛出，则测试通过
        $this->assertTrue(true);
    }
}