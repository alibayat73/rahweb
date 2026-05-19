<?php

namespace App\Contracts;

use Illuminate\Http\Response;

readonly class DeliveryResult
{
    public function __construct(
        public bool $success,
        public int $statusCode,
        public string $message,
    ) {}

    public static function success(int $statusCode = Response::HTTP_OK, string $message = 'OK'): self
    {
        return new self(true, $statusCode, $message);
    }

    public static function failure(int $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR, string $message = 'Internal Server Error'): self
    {
        return new self(false, $statusCode, $message);
    }
}
