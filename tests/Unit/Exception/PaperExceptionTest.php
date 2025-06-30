<?php

namespace Tourze\TestPaperBundle\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Tourze\TestPaperBundle\Exception\PaperException;

class PaperExceptionTest extends TestCase
{
    public function testDefaultMessage(): void
    {
        $exception = new PaperException();
        $this->assertEquals('试卷异常', $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
    }

    public function testCustomMessage(): void
    {
        $message = '题目已存在于试卷中';
        $exception = new PaperException($message);
        $this->assertEquals($message, $exception->getMessage());
    }

    public function testCustomCode(): void
    {
        $code = 1001;
        $exception = new PaperException('试卷异常', $code);
        $this->assertEquals($code, $exception->getCode());
    }

    public function testInheritsFromInvalidArgumentException(): void
    {
        $exception = new PaperException();
        $this->assertInstanceOf(\InvalidArgumentException::class, $exception);
    }
}