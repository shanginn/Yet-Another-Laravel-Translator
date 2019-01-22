<?php

namespace Shanginn\Yalt\Http\Exceptions;

use RuntimeException;
use Throwable;

class TranslationMustBeArrayException extends RuntimeException
{
    /**
     * UnsupportedLocaleException constructor.
     *
     * @param string $key
     * @param string $value
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct($key, $value, $code = 0, Throwable $previous = null)
    {
        $message = "Translation '$key' must be array. '$value' given";

        parent::__construct($message, $code, $previous);
    }
}