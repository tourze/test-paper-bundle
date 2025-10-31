<?php

namespace Tourze\TestPaperBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\TestPaperBundle\Entity\PaperTemplate;
use Tourze\TestPaperBundle\Entity\TemplateRule;

/**
 * @internal
 */
#[CoversClass(TemplateRule::class)]
final class TemplateRuleTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        $rule = new TemplateRule();

        // 设置必需的 template 关联
        $template = new PaperTemplate();
        $template->setName('测试模板 - TemplateRule');
        $rule->setTemplate($template);

        return $rule;
    }

    /**
     * @return iterable<array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield ['categoryId', 'test-category-123'];
        yield ['questionType', 'single_choice'];
        yield ['difficulty', 'medium'];
        yield ['questionCount', 8];
        yield ['scorePerQuestion', 12];
        yield ['sort', 3];
        yield ['excludeUsed', true];
        yield ['tagFilters', ['tag1', 'tag2', 'tag3']];
    }
}
