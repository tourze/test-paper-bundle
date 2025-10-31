<?php

namespace Tourze\TestPaperBundle\Exception;

class SessionException extends \Exception
{
    public function __construct(string $message = '会话异常', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
