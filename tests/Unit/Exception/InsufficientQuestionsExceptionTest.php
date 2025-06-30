<?php

namespace Tourze\TestPaperBundle\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Tourze\TestPaperBundle\Exception\InsufficientQuestionsException;

class InsufficientQuestionsExceptionTest extends TestCase
{
    public function testDefaultMessage(): void
    {
        $exception = new InsufficientQuestionsException();
        $this->assertEquals('题目数量不足', $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
    }

    public function testCustomMessage(): void
    {
        $message = '题库中题目数量不足，无法生成试卷';
        $exception = new InsufficientQuestionsException($message);
        $this->assertEquals($message, $exception->getMessage());
    }

    public function testCustomCode(): void
    {
        $code = 3001;
        $exception = new InsufficientQuestionsException('题目数量不足', $code);
        $this->assertEquals($code, $exception->getCode());
    }

    public function testInheritsFromException(): void
    {
        $exception = new InsufficientQuestionsException();
        $this->assertInstanceOf(\Exception::class, $exception);
    }
}