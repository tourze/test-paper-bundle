<?php

namespace Tourze\TestPaperBundle\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Tourze\TestPaperBundle\Exception\InvalidTemplateException;

class InvalidTemplateExceptionTest extends TestCase
{
    public function testDefaultMessage(): void
    {
        $exception = new InvalidTemplateException();
        $this->assertEquals('无效的模板配置', $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
    }

    public function testCustomMessage(): void
    {
        $message = '模板规则配置无效';
        $exception = new InvalidTemplateException($message);
        $this->assertEquals($message, $exception->getMessage());
    }

    public function testCustomCode(): void
    {
        $code = 4001;
        $exception = new InvalidTemplateException('无效的模板配置', $code);
        $this->assertEquals($code, $exception->getCode());
    }

    public function testInheritsFromException(): void
    {
        $exception = new InvalidTemplateException();
        $this->assertInstanceOf(\Exception::class, $exception);
    }
}