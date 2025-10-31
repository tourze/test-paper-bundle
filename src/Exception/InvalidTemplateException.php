<?php

namespace Tourze\TestPaperBundle\Exception;

class InvalidTemplateException extends \Exception
{
    public function __construct(string $message = '无效的模板配置', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
