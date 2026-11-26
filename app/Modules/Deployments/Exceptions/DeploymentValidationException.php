<?php

declare(strict_types=1);

namespace App\Modules\Deployments\Exceptions;

use RuntimeException;

class DeploymentValidationException extends RuntimeException
{
    public function __construct(public readonly string $failureCode, string $message)
    {
        parent::__construct($message);
    }
}
