<?php

namespace App\Dto\Health;

final readonly class HealthResponseDto
{
    public function __construct(
        public string $status,
        public string $service,
        public string $version,
    ) {
    }

    /** @return array{status: string, service: string, version: string} */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'service' => $this->service,
            'version' => $this->version,
        ];
    }
}

