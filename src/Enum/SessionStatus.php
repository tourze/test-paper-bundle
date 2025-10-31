<?php

namespace Tourze\TestPaperBundle\Enum;

use Tourze\Arrayable\Arrayable;
use Tourze\EnumExtra\BadgeInterface;
use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * @implements Arrayable<string, string|bool>
 */
enum SessionStatus: string implements Arrayable, Itemable, Labelable, Selectable, BadgeInterface
{
    use ItemTrait;
    use SelectTrait;

    case PENDING = 'pending';           // 待开始
    case IN_PROGRESS = 'in_progress';   // 进行中
    case COMPLETED = 'completed';       // 已完成
    case EXPIRED = 'expired';           // 已过期
    case CANCELLED = 'cancelled';       // 已取消

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

    public function getBadge(): string
    {
        return match ($this) {
            self::PENDING => self::SECONDARY,
            self::IN_PROGRESS => self::PRIMARY,
            self::COMPLETED => self::SUCCESS,
            self::EXPIRED => self::WARNING,
            self::CANCELLED => self::DANGER,
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
