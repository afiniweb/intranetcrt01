<?php

namespace App\Tests\Service\Health;

use App\Service\Health\HealthService;
use PHPUnit\Framework\TestCase;

final class HealthServiceTest extends TestCase
{
    public function testItReportsApiAsAvailable(): void
    {
        $response = (new HealthService())->check();

        self::assertSame('ok', $response->status);
        self::assertSame('intranet-crt01-api', $response->service);
    }
}

