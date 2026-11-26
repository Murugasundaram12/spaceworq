<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class PaymentGatewayNotConfiguredException extends HttpException
{
    public function __construct(string $message = 'Payment gateway credentials are not configured. Payment processing is disabled.')
    {
        parent::__construct(503, $message);
    }
}
