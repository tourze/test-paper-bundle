<?php

namespace Tourze\TestPaperBundle\Enum;

use Tourze\Arrayable\Arrayable;
use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum PaperStatus: string implements Arrayable, Itemable, Labelable, Selectable
{
    use ItemTrait;
    use SelectTrait;
    case DRAFT = 'draft';           // 草稿
    case PUBLISHED = 'published';   // 已发布
    case ARCHIVED = 'archived';     // 已归档
    case CLOSED = 'closed';         // 已关闭

    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'label' => $this->getLabel(),
            'color' => $this->getColor(),
        ];
    }

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
}