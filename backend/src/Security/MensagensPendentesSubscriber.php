<?php

namespace App\Security;

use App\Entity\Usuario;
use App\Service\Mensagem\MensagemService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final readonly class MensagensPendentesSubscriber implements EventSubscriberInterface
{
    public function __construct(private MensagemService $mensagens, private TokenStorageInterface $tokens) {}
    public static function getSubscribedEvents(): array { return [KernelEvents::CONTROLLER => 'verificar']; }
    public function verificar(ControllerEvent $evento): void
    {
        if (!$evento->isMainRequest()) return;
        $r = $evento->getRequest(); $path = $r->getPathInfo();
        if (!str_starts_with($path, '/api/v1/') || str_starts_with($path, '/api/v1/auth/') || $path === '/api/v1/health'
            || $path === '/api/v1/mensagens/pendente' || preg_match('#^/api/v1/mensagens/\d+/(apresentar|responder)$#D', $path)) return;
        $usuario = $this->tokens->getToken()?->getUser();
        if (!$usuario instanceof Usuario) return;
        if ($this->mensagens->pendente($usuario, $r->getSession())) {
            $evento->setController(static fn() => new JsonResponse(['mensagem' => 'Leia as mensagens pendentes para continuar.', 'codigo' => 'MENSAGEM_PENDENTE'], 423, ['Cache-Control' => 'no-store']));
        }
    }
}
