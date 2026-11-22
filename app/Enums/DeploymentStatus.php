<?php

declare(strict_types=1);

namespace App\Enums;

enum DeploymentStatus: string
{
    case Queued = 'queued';
    case Preparing = 'preparing';
    case Running = 'running';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
}
