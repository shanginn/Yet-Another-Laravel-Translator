<?php

namespace Shanginn\Yalt\Http\Exceptions;

use RuntimeException;
use Throwable;

class UnsupportedLocaleException extends RuntimeException
{
    /**
     * UnsupportedLocaleException constructor.
     *
     * @param string $locale
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct($locale, $code = 0, Throwable $previous = null)
    {
        $message = "Locale '$locale' is not supported";

        parent::__construct($message, $code, $previous);
    }
}