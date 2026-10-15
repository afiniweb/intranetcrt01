<?php

namespace App\Tests\Service\Publicacao;

use App\Entity\{Escritorio, Instituicao, Publicacao, TipoConteudo, Usuario};
use App\Repository\{PublicacaoRepository, TipoConteudoRepository};
use App\Service\Publicacao\PublicacaoService;
use Doctrine\ORM\{EntityManagerInterface};
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class LeituraLeitorTest extends TestCase
{
    public static function cenarios(): array
    {
        return [
            'publicada na própria instituição' => [false, false, true],
            'arquivada' => [true, false, false],
            'outra instituição' => [false, true, false],
        ];
    }

    #[DataProvider('cenarios')]
    public function testAcessoAoPdf(bool $arquivada, bool $outraInstituicao, bool $permitido): void
    {
        $instituicao = new Instituicao('Instituição', 'TESTE', null);
        $outra = new Instituicao('Outra instituição', 'OUTRA', null);
        $id = new \ReflectionProperty(Instituicao::class, 'id');
        $id->setValue($instituicao, 1);
        $id->setValue($outra, 2);
        $escritorio = new Escritorio($instituicao, 'Sede', 'DF', 'Brasília', null);
        $autor = new Usuario($instituicao, $escritorio, 'Autor', 'autor@example.com', '', 'PUBLICADOR', false);
        $instituicaoLeitor = $outraInstituicao ? $outra : $instituicao;
        $leitor = new Usuario($instituicaoLeitor, new Escritorio($instituicaoLeitor, 'Regional', 'DF', 'Brasília', null), 'Leitor', 'leitor@example.com', '', 'LEITOR', false);
        $publicacao = new Publicacao(new TipoConteudo($instituicao, $autor, 'Comunicados', null), $autor, 'Título', 'Corpo', null);
        $publicacao->definirArquivoPdf('teste.pdf');
        if ($arquivada) $publicacao->arquivar();

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getClassMetadata')->willReturn(new ClassMetadata(Publicacao::class));
        $em->expects(self::once())->method('find')->with(Publicacao::class, 1)->willReturn($publicacao);
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($em);
        $raiz = sys_get_temp_dir().'/leitor-'.bin2hex(random_bytes(8));
        mkdir($raiz.'/var/uploads/publicacoes', 0700, true);
        $arquivo = $raiz.'/var/uploads/publicacoes/teste.pdf';
        file_put_contents($arquivo, '%PDF-');
        $service = new PublicacaoService(new PublicacaoRepository($registry), new TipoConteudoRepository($registry), $em, $raiz);

        try {
            if (!$permitido) $this->expectException(NotFoundHttpException::class);
            self::assertSame($arquivo, $service->caminhoArquivo(1, $leitor));
        } finally {
            unlink($arquivo);
            rmdir($raiz.'/var/uploads/publicacoes');
            rmdir($raiz.'/var/uploads');
            rmdir($raiz.'/var');
            rmdir($raiz);
        }
    }
}
