<?php

namespace App\Tests\Security;

use App\Security\CsrfApiSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class CsrfApiSubscriberTest extends TestCase
{
    public function testRejeitaPostSemToken(): void
    {
        $gerenciador = $this->createMock(CsrfTokenManagerInterface::class);
        $gerenciador->expects(self::never())->method('isTokenValid');
        $evento = $this->evento(Request::create('/api/v1/publicacoes', 'POST'));

        (new CsrfApiSubscriber($gerenciador))->validar($evento);

        self::assertSame(403, $evento->getResponse()?->getStatusCode());
        self::assertSame('1', $evento->getResponse()?->headers->get('X-CSRF-Error'));
    }

    public function testAceitaPostComTokenValido(): void
    {
        $gerenciador = $this->createMock(CsrfTokenManagerInterface::class);
        $gerenciador->expects(self::once())->method('isTokenValid')->with(self::callback(static fn(CsrfToken $token): bool => $token->getId() === CsrfApiSubscriber::TOKEN_ID && $token->getValue() === 'token-valido'))->willReturn(true);
        $request = Request::create('/api/v1/publicacoes', 'POST'); $request->headers->set('X-CSRF-TOKEN', 'token-valido'); $evento = $this->evento($request);

        (new CsrfApiSubscriber($gerenciador))->validar($evento);

        self::assertNull($evento->getResponse());
    }

    public function testIgnoraRequisicaoGet(): void
    {
        $gerenciador = $this->createMock(CsrfTokenManagerInterface::class);
        $gerenciador->expects(self::never())->method('isTokenValid'); $evento = $this->evento(Request::create('/api/v1/dashboard', 'GET'));
        (new CsrfApiSubscriber($gerenciador))->validar($evento);
        self::assertNull($evento->getResponse());
    }

    private function evento(Request $request): RequestEvent
    {
        return new RequestEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);
    }
}
