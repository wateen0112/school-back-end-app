<?php

namespace App\Services\Ai;

use RuntimeException;

class AiNotConfiguredException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('المساعد الذكي غير مُعَدّ. يرجى تكوين مفتاح Gemini في الإعدادات.');
    }
}