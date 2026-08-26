<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

final class AutenticacaoFailureHandler implements AuthenticationFailureHandlerInterface
{
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        if ($exception instanceof TooManyLoginAttemptsAuthenticationException) {
            $minutos = max(1, (int) ($exception->getMessageData()['%minutes%'] ?? 1));
            return new JsonResponse(['mensagem' => 'Muitas tentativas de acesso. Aguarde e tente novamente.'], Response::HTTP_TOO_MANY_REQUESTS, ['Retry-After' => (string) ($minutos * 60)]);
        }

        return new JsonResponse(['mensagem' => 'E-mail ou senha inválidos.'], Response::HTTP_UNAUTHORIZED);
    }
}
