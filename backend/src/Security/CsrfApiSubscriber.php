<?php

namespace App\Security;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final readonly class CsrfApiSubscriber implements EventSubscriberInterface
{
    public const TOKEN_ID = 'api_mutation';
    private const METODOS_SEGUROS = ['GET', 'HEAD', 'OPTIONS'];

    public function __construct(private CsrfTokenManagerInterface $tokenManager) {}

    public static function getSubscribedEvents(): array { return [KernelEvents::REQUEST => ['validar', 10]]; }

    public function validar(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) return;
        $request = $event->getRequest();
        if (!str_starts_with($request->getPathInfo(), '/api/') || in_array($request->getMethod(), self::METODOS_SEGUROS, true)) return;

        $valor = (string) $request->headers->get('X-CSRF-TOKEN', '');
        if ($valor !== '' && $this->tokenManager->isTokenValid(new CsrfToken(self::TOKEN_ID, $valor))) return;

        $event->setResponse(new JsonResponse(
            ['mensagem' => 'Token de segurança ausente ou inválido. Atualize a página e tente novamente.'],
            Response::HTTP_FORBIDDEN,
            ['X-CSRF-Error' => '1'],
        ));
    }
}
