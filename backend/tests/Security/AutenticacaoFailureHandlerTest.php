<?php

namespace App\Tests\Security;

use App\Security\AutenticacaoFailureHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;

final class AutenticacaoFailureHandlerTest extends TestCase
{
    public function testNaoRevelaSeOUsuarioExiste(): void
    {
        $resposta = (new AutenticacaoFailureHandler())->onAuthenticationFailure(new Request(), new AuthenticationException('Detalhe interno'));
        self::assertSame(401, $resposta->getStatusCode());
        self::assertSame(['mensagem' => 'E-mail ou senha inválidos.'], json_decode((string) $resposta->getContent(), true));
    }

    public function testInformaEsperaQuandoLimiteForAtingido(): void
    {
        $resposta = (new AutenticacaoFailureHandler())->onAuthenticationFailure(new Request(), new TooManyLoginAttemptsAuthenticationException(2));
        self::assertSame(429, $resposta->getStatusCode());
        self::assertSame('120', $resposta->headers->get('Retry-After'));
        self::assertSame(['mensagem' => 'Muitas tentativas de acesso. Aguarde e tente novamente.'], json_decode((string) $resposta->getContent(), true));
    }
}
