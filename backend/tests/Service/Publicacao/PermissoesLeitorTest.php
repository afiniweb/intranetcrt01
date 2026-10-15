<?php

namespace App\Tests\Service\Publicacao;

use App\Dto\Publicacao\SalvarPublicacaoDto;
use App\Entity\Escritorio;
use App\Entity\Instituicao;
use App\Entity\Usuario;
use App\Repository\PublicacaoRepository;
use App\Repository\TipoConteudoRepository;
use App\Service\Publicacao\PublicacaoService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class PermissoesLeitorTest extends TestCase
{
    public static function operacoes(): array
    {
        return [['listar'], ['criar'], ['arquivar'], ['reativar']];
    }

    #[DataProvider('operacoes')]
    public function testLeitorNaoPodeGerirPublicacoes(string $operacao): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        // O bloqueio deve ocorrer antes de qualquer consulta ou gravação no banco.
        $registry->expects(self::never())->method('getManagerForClass');
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('flush');
        $service = new PublicacaoService(new PublicacaoRepository($registry), new TipoConteudoRepository($registry), $em, '/tmp');
        $instituicao = new Instituicao('Instituição', 'TESTE', null);
        $escritorio = new Escritorio($instituicao, 'Sede', 'DF', 'Brasília', null);
        $leitor = new Usuario($instituicao, $escritorio, 'Leitor', 'leitor@example.com', '', 'LEITOR', false);

        $this->expectException(AccessDeniedHttpException::class);
        match ($operacao) {
            'listar' => $service->listar($leitor, '', 1, 10),
            'criar' => $service->criar($leitor, new SalvarPublicacaoDto(1, 'Título', 'Corpo', 'https://example.com'), null),
            'arquivar' => $service->arquivar(1, $leitor),
            'reativar' => $service->reativar(1, $leitor),
        };
    }
}
