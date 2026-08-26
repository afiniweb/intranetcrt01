<?php

namespace App\Controller\Api\V1;

use App\Entity\Usuario;
use App\Service\Dashboard\DashboardService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/dashboard')] #[OA\Tag(name: 'Dashboard')]
final class DashboardController extends AbstractController
{
    public function __construct(private readonly DashboardService $service) {}
    #[Route('/tipos-conteudo', methods: ['GET'])] public function tipos(): JsonResponse { return new JsonResponse($this->service->listarTipos($this->usuario())); }
    #[Route('/tipos-conteudo/{id<\d+>}/publicacoes', methods: ['GET'])] public function publicacoes(int $id, Request $request): JsonResponse { $pagina = max(1, $request->query->getInt('pagina', 1)); $porPagina = min(50, max(1, $request->query->getInt('porPagina', 10))); return new JsonResponse($this->service->listarPublicacoes($id, $this->usuario(), $pagina, $porPagina)); }
    private function usuario(): Usuario { $usuario = $this->getUser(); if (!$usuario instanceof Usuario) throw $this->createAccessDeniedException(); return $usuario; }
}
