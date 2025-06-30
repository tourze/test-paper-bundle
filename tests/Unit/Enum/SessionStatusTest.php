<?php

namespace Tourze\TestPaperBundle\Tests\Unit\Enum;

use PHPUnit\Framework\TestCase;
use Tourze\TestPaperBundle\Enum\SessionStatus;

class SessionStatusTest extends TestCase
{
    public function testValues(): void
    {
        $this->assertEquals('pending', SessionStatus::PENDING->value);
        $this->assertEquals('in_progress', SessionStatus::IN_PROGRESS->value);
        $this->assertEquals('completed', SessionStatus::COMPLETED->value);
        $this->assertEquals('expired', SessionStatus::EXPIRED->value);
        $this->assertEquals('cancelled', SessionStatus::CANCELLED->value);
    }

    public function testLabels(): void
    {
        $this->assertEquals('待开始', SessionStatus::PENDING->getLabel());
        $this->assertEquals('进行中', SessionStatus::IN_PROGRESS->getLabel());
        $this->assertEquals('已完成', SessionStatus::COMPLETED->getLabel());
        $this->assertEquals('已过期', SessionStatus::EXPIRED->getLabel());
        $this->assertEquals('已取消', SessionStatus::CANCELLED->getLabel());
    }

    public function testColors(): void
    {
        $this->assertEquals('default', SessionStatus::PENDING->getColor());
        $this->assertEquals('processing', SessionStatus::IN_PROGRESS->getColor());
        $this->assertEquals('success', SessionStatus::COMPLETED->getColor());
        $this->assertEquals('warning', SessionStatus::EXPIRED->getColor());
        $this->assertEquals('error', SessionStatus::CANCELLED->getColor());
    }

    public function testIsActive(): void
    {
        $this->assertTrue(SessionStatus::PENDING->isActive());
        $this->assertTrue(SessionStatus::IN_PROGRESS->isActive());
        $this->assertFalse(SessionStatus::COMPLETED->isActive());
        $this->assertFalse(SessionStatus::EXPIRED->isActive());
        $this->assertFalse(SessionStatus::CANCELLED->isActive());
    }

    public function testIsFinished(): void
    {
        $this->assertFalse(SessionStatus::PENDING->isFinished());
        $this->assertFalse(SessionStatus::IN_PROGRESS->isFinished());
        $this->assertTrue(SessionStatus::COMPLETED->isFinished());
        $this->assertTrue(SessionStatus::EXPIRED->isFinished());
        $this->assertTrue(SessionStatus::CANCELLED->isFinished());
    }

    public function testToArray(): void
    {
        $array = SessionStatus::PENDING->toArray();
        $this->assertEquals([
            'value' => 'pending',
            'label' => '待开始',
            'color' => 'default',
            'isActive' => true,
            'isFinished' => false,
        ], $array);
    }
}