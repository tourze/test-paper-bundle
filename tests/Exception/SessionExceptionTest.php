<?php

namespace Tourze\TestPaperBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\TestPaperBundle\Exception\SessionException;

/**
 * @internal
 */
#[CoversClass(SessionException::class)]
final class SessionExceptionTest extends AbstractExceptionTestCase
{
    public function testDefaultMessage(): void
    {
        $exception = new SessionException();
        $this->assertEquals('会话异常', $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
    }

    public function testCustomMessage(): void
    {
        $message = '会话已过期';
        $exception = new SessionException($message);
        $this->assertEquals($message, $exception->getMessage());
    }

    public function testCustomCode(): void
    {
        $code = 2001;
        $exception = new SessionException('会话异常', $code);
        $this->assertEquals($code, $exception->getCode());
    }

    public function testInheritsFromException(): void
    {
        $exception = new SessionException();
        $this->assertInstanceOf(\Exception::class, $exception);
    }
}
