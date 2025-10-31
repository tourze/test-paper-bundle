<?php

namespace Tourze\TestPaperBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;
use Tourze\TestPaperBundle\DependencyInjection\TestPaperExtension;

/**
 * @internal
 */
#[CoversClass(TestPaperExtension::class)]
final class TestPaperExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
    private TestPaperExtension $extension;

    private ContainerBuilder $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extension = new TestPaperExtension();
        $this->container = new ContainerBuilder();
        $this->container->setParameter('kernel.environment', 'test');
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
        $this->assertInstanceOf(Extension::class, $this->extension);
    }
}
