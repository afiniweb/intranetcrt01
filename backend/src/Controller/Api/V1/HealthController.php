<?php

namespace App\Controller\Api\V1;

use App\Service\Health\HealthService;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Infrastructure')]
final class HealthController
{
    #[Route('/api/v1/health', name: 'api_v1_health_check', methods: ['GET'])]
    #[OA\Get(
        summary: 'Checks API availability',
        responses: [
            new OA\Response(
                response: 200,
                description: 'API is available',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'ok'),
                        new OA\Property(property: 'service', type: 'string', example: 'intranet-crt01-api'),
                        new OA\Property(property: 'version', type: 'string', example: '0.1.0'),
                    ],
                    type: 'object',
                ),
            ),
        ],
    )]
    public function check(HealthService $healthService): JsonResponse
    {
        return new JsonResponse($healthService->check()->toArray());
    }
}

