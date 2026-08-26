<?php

namespace App\Controller\Api\V1;

use App\Dto\Usuario\UsuarioRespostaDto;
use App\Entity\Usuario;
use App\Security\CsrfApiSubscriber;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('/api/v1/auth')]
final class AutenticacaoController extends AbstractController
{
    #[Route('/csrf', methods: ['GET'])]
    public function csrf(CsrfTokenManagerInterface $tokenManager): JsonResponse { return new JsonResponse(['token' => $tokenManager->getToken(CsrfApiSubscriber::TOKEN_ID)->getValue()]); }
    #[Route('/login', methods: ['POST'])]
    public function login(): Response { return new JsonResponse(null, Response::HTTP_NO_CONTENT); }
    #[Route('/me', methods: ['GET'])]
    public function me(): JsonResponse { $usuario = $this->getUser(); if (!$usuario instanceof Usuario || !$usuario->isAtivo()) return new JsonResponse(['mensagem' => 'Não autenticado.'], Response::HTTP_UNAUTHORIZED); return new JsonResponse(UsuarioRespostaDto::daEntidade($usuario)->paraArray()); }
    #[Route('/logout', methods: ['POST'])]
    public function logout(): void { throw new \LogicException('A rota é processada pelo firewall de segurança.'); }
    #[Route('/logout-sucesso', methods: ['GET'])]
    public function logoutSucesso(): Response { return new Response(status: Response::HTTP_NO_CONTENT); }
}
