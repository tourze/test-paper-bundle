<?php

namespace Tourze\TestPaperBundle\Enum;

use Tourze\Arrayable\Arrayable;

enum PaperGenerationType: string implements Arrayable
{
    case MANUAL = 'manual';         // 手动选题
    case TEMPLATE = 'template';     // 模板组卷
    case RANDOM = 'random';         // 随机组卷
    case INTELLIGENT = 'intelligent'; // 智能组卷
    case ADAPTIVE = 'adaptive';     // 自适应组卷

    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'label' => $this->getLabel(),
            'description' => $this->getDescription(),
        ];
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::MANUAL => '手动选题',
            self::TEMPLATE => '模板组卷',
            self::RANDOM => '随机组卷',
            self::INTELLIGENT => '智能组卷',
            self::ADAPTIVE => '自适应组卷',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::MANUAL => '手动选择题目组成试卷',
            self::TEMPLATE => '根据预设模板规则自动生成试卷',
            self::RANDOM => '从题库中随机抽取题目',
            self::INTELLIGENT => '根据知识点分布和难度比例智能组卷',
            self::ADAPTIVE => '根据学习者能力动态调整题目难度',
        };
    }
}