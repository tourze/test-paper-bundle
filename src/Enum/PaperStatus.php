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
 * @implements Arrayable<string, string>
 */
enum PaperStatus: string implements Arrayable, BadgeInterface, Itemable, Labelable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case DRAFT = 'draft';           // 草稿
    case PUBLISHED = 'published';   // 已发布
    case ARCHIVED = 'archived';     // 已归档
    case CLOSED = 'closed';         // 已关闭

    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT => '草稿',
            self::PUBLISHED => '已发布',
            self::ARCHIVED => '已归档',
            self::CLOSED => '已关闭',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::DRAFT => 'default',
            self::PUBLISHED => 'success',
            self::ARCHIVED => 'warning',
            self::CLOSED => 'error',
        };
    }

    public function getBadge(): string
    {
        return match ($this) {
            self::DRAFT => BadgeInterface::SECONDARY,
            self::PUBLISHED => BadgeInterface::SUCCESS,
            self::ARCHIVED => BadgeInterface::WARNING,
            self::CLOSED => BadgeInterface::DANGER,
        };
    }
}
