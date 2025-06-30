<?php

namespace Tourze\TestPaperBundle\Tests\Unit\Enum;

use PHPUnit\Framework\TestCase;
use Tourze\TestPaperBundle\Enum\PaperStatus;

class PaperStatusTest extends TestCase
{
    public function testValues(): void
    {
        $this->assertEquals('draft', PaperStatus::DRAFT->value);
        $this->assertEquals('published', PaperStatus::PUBLISHED->value);
        $this->assertEquals('archived', PaperStatus::ARCHIVED->value);
        $this->assertEquals('closed', PaperStatus::CLOSED->value);
    }

    public function testLabels(): void
    {
        $this->assertEquals('草稿', PaperStatus::DRAFT->getLabel());
        $this->assertEquals('已发布', PaperStatus::PUBLISHED->getLabel());
        $this->assertEquals('已归档', PaperStatus::ARCHIVED->getLabel());
        $this->assertEquals('已关闭', PaperStatus::CLOSED->getLabel());
    }

    public function testColors(): void
    {
        $this->assertEquals('default', PaperStatus::DRAFT->getColor());
        $this->assertEquals('success', PaperStatus::PUBLISHED->getColor());
        $this->assertEquals('warning', PaperStatus::ARCHIVED->getColor());
        $this->assertEquals('error', PaperStatus::CLOSED->getColor());
    }

    public function testToArray(): void
    {
        $array = PaperStatus::DRAFT->toArray();
        $this->assertEquals([
            'value' => 'draft',
            'label' => '草稿',
            'color' => 'default',
        ], $array);
    }
}