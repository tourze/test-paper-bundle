<?php

namespace Tourze\TestPaperBundle\Tests\Unit\Enum;

use PHPUnit\Framework\TestCase;
use Tourze\TestPaperBundle\Enum\PaperGenerationType;

class PaperGenerationTypeTest extends TestCase
{
    public function testValues(): void
    {
        $this->assertEquals('manual', PaperGenerationType::MANUAL->value);
        $this->assertEquals('template', PaperGenerationType::TEMPLATE->value);
        $this->assertEquals('random', PaperGenerationType::RANDOM->value);
        $this->assertEquals('intelligent', PaperGenerationType::INTELLIGENT->value);
        $this->assertEquals('adaptive', PaperGenerationType::ADAPTIVE->value);
    }

    public function testLabels(): void
    {
        $this->assertEquals('手动选题', PaperGenerationType::MANUAL->getLabel());
        $this->assertEquals('模板组卷', PaperGenerationType::TEMPLATE->getLabel());
        $this->assertEquals('随机组卷', PaperGenerationType::RANDOM->getLabel());
        $this->assertEquals('智能组卷', PaperGenerationType::INTELLIGENT->getLabel());
        $this->assertEquals('自适应组卷', PaperGenerationType::ADAPTIVE->getLabel());
    }

    public function testDescriptions(): void
    {
        $this->assertEquals('手动选择题目组成试卷', PaperGenerationType::MANUAL->getDescription());
        $this->assertEquals('根据预设模板规则自动生成试卷', PaperGenerationType::TEMPLATE->getDescription());
        $this->assertEquals('从题库中随机抽取题目', PaperGenerationType::RANDOM->getDescription());
        $this->assertEquals('根据知识点分布和难度比例智能组卷', PaperGenerationType::INTELLIGENT->getDescription());
        $this->assertEquals('根据学习者能力动态调整题目难度', PaperGenerationType::ADAPTIVE->getDescription());
    }

    public function testToArray(): void
    {
        $array = PaperGenerationType::MANUAL->toArray();
        $this->assertEquals([
            'value' => 'manual',
            'label' => '手动选题',
            'description' => '手动选择题目组成试卷',
        ], $array);
    }
}