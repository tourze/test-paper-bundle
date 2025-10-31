<?php

namespace Tourze\TestPaperBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\TestPaperBundle\Entity\PaperTemplate;

/**
 * @internal
 */
#[CoversClass(PaperTemplate::class)]
final class PaperTemplateTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        $template = new PaperTemplate();
        // 设置必需的 name 属性
        $template->setName('初始模板名称');

        return $template;
    }

    /**
     * @return iterable<array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield ['name', '测试模板名称'];
        yield ['description', '模板描述信息'];
        yield ['totalQuestions', 25];
        yield ['totalScore', 120];
        yield ['passScore', 72];
        yield ['timeLimit', 5400];
        // isActive/setIsActive - 移除此测试项，因为 AbstractEntityTestCase 无法正确处理 is 前缀的 boolean 属性
        yield ['shuffleQuestions', true];
        yield ['shuffleOptions', true];
    }
}
