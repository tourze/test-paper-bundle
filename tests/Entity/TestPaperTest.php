<?php

namespace Tourze\TestPaperBundle\Tests\Entity;

use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\TestPaperBundle\Entity\TestPaper;
use Tourze\TestPaperBundle\Enum\PaperGenerationType;
use Tourze\TestPaperBundle\Enum\PaperStatus;

/**
 * @internal
 */
#[CoversClass(TestPaper::class)]
final class TestPaperTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        $paper = new TestPaper();
        // 设置必需的 title 属性（unique 约束）
        $paper->setTitle('初始测试试卷标题 - ' . uniqid());

        return $paper;
    }

    /**
     * @return iterable<array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        // title 需要 unique，使用不同的值
        yield ['title', '测试试卷标题 - ' . uniqid()];
        yield ['description', '试卷描述信息'];
        yield ['status', PaperStatus::PUBLISHED];
        yield ['generationType', PaperGenerationType::TEMPLATE];
        yield ['totalScore', 150];
        yield ['passScore', 90];
        yield ['timeLimit', 7200];
        yield ['questionCount', 20];
        yield ['allowRetake', false];
        yield ['maxAttempts', 3];
        yield ['randomizeQuestions', true];
        yield ['randomizeOptions', false];
    }
}
