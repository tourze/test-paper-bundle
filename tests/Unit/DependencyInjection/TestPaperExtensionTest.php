<?php

namespace Tourze\TestPaperBundle\Tests\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\TestPaperBundle\DependencyInjection\TestPaperExtension;

class TestPaperExtensionTest extends TestCase
{
    private TestPaperExtension $extension;
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $this->extension = new TestPaperExtension();
        $this->container = new ContainerBuilder();
    }

    public function testLoad(): void
    {
        $configs = [];
        $this->extension->load($configs, $this->container);
        
        // 验证服务是否正确加载
        $this->assertTrue($this->container->hasDefinition('Tourze\TestPaperBundle\Service\PaperService'));
        $this->assertTrue($this->container->hasDefinition('Tourze\TestPaperBundle\Service\TestSessionService'));
    }

    public function testInstanceOfExtension(): void
    {
        $this->assertInstanceOf(\Symfony\Component\DependencyInjection\Extension\Extension::class, $this->extension);
    }
}