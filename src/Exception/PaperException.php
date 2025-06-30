<?php

namespace Tourze\TestPaperBundle\Exception;

class PaperException extends \InvalidArgumentException
{
    public function __construct(string $message = '试卷异常', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}