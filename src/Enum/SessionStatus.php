<?php

namespace Tourze\TestPaperBundle\Enum;

use Tourze\Arrayable\Arrayable;

enum SessionStatus: string implements Arrayable
{
    case PENDING = 'pending';           // 待开始
    case IN_PROGRESS = 'in_progress';   // 进行中
    case COMPLETED = 'completed';       // 已完成
    case EXPIRED = 'expired';           // 已过期
    case CANCELLED = 'cancelled';       // 已取消

    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'label' => $this->getLabel(),
            'color' => $this->getColor(),
            'isActive' => $this->isActive(),
            'isFinished' => $this->isFinished(),
        ];
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => '待开始',
            self::IN_PROGRESS => '进行中',
            self::COMPLETED => '已完成',
            self::EXPIRED => '已过期',
            self::CANCELLED => '已取消',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PENDING => 'default',
            self::IN_PROGRESS => 'processing',
            self::COMPLETED => 'success',
            self::EXPIRED => 'warning',
            self::CANCELLED => 'error',
        };
    }

    public function isActive(): bool
    {
        return match ($this) {
            self::PENDING, self::IN_PROGRESS => true,
            default => false,
        };
    }

    public function isFinished(): bool
    {
        return match ($this) {
            self::COMPLETED, self::EXPIRED, self::CANCELLED => true,
            default => false,
        };
    }
}