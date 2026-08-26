<?php

namespace App\Service\Health;

use App\Dto\Health\HealthResponseDto;

final class HealthService
{
    public function check(): HealthResponseDto
    {
        return new HealthResponseDto(
            status: 'ok',
            service: 'intranet-crt01-api',
            version: '0.1.0',
        );
    }
}

