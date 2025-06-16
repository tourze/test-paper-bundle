<?php

namespace Tourze\TestPaperBundle\Exception;

class InsufficientQuestionsException extends \Exception
{
    public function __construct(string $message = '题目数量不足', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}