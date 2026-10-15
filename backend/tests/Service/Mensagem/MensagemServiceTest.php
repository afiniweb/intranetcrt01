<?php

namespace App\Tests\Service\Mensagem;

use App\Entity\{CienciaMensagem, Escritorio, Instituicao, Mensagem, Usuario};
use App\Service\Mensagem\MensagemService;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;
use Doctrine\ORM\{EntityManager, ORMSetup};
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Exception\{AccessDeniedHttpException, BadRequestHttpException, ConflictHttpException, NotFoundHttpException};

final class MensagemServiceTest extends TestCase
{
    private EntityManager $em;
    private MensagemService $service;
    private Session $sessao;
    private Usuario $admin;
    private Usuario $leitor;
    private Usuario $outroAdmin;
    private string $schema;

    protected function setUp(): void
    {
        $url = getenv('MENSAGENS_TEST_DATABASE_URL');
        if (!$url) self::markTestSkipped('Defina MENSAGENS_TEST_DATABASE_URL; os testes usam um schema isolado e descartável.');
        $conn = DriverManager::getConnection((new DsnParser(['postgresql' => 'pdo_pgsql']))->parse($url));
        $this->schema = 'teste_mensagens_'.bin2hex(random_bytes(6));
        $conn->executeStatement('CREATE SCHEMA '.$this->schema);
        $conn->executeStatement('SET search_path TO '.$this->schema);
        $config = ORMSetup::createAttributeMetadataConfiguration([dirname(__DIR__, 3).'/src/Entity'], true);
        $this->em = new EntityManager($conn, $config);
        (new SchemaTool($this->em))->createSchema($this->em->getMetadataFactory()->getAllMetadata());
        $instituicao = new Instituicao('Instituição', 'TESTE', null);
        $outra = new Instituicao('Outra', 'OUTRA', null);
        $escritorio = new Escritorio($instituicao, 'Sede', 'DF', 'Brasília', null);
        $outroEscritorio = new Escritorio($outra, 'Sede', 'DF', 'Brasília', null);
        $this->admin = new Usuario($instituicao, $escritorio, 'Admin', 'admin@example.com', '', 'ADMIN', false);
        $this->leitor = new Usuario($instituicao, $escritorio, 'Leitor', 'leitor@example.com', '', 'LEITOR', false);
        $this->outroAdmin = new Usuario($outra, $outroEscritorio, 'Outro admin', 'outro@example.com', '', 'ADMIN', false);
        foreach ([$instituicao, $outra, $escritorio, $outroEscritorio, $this->admin, $this->leitor, $this->outroAdmin] as $item) $this->em->persist($item);
        $this->em->flush();
        $this->service = new MensagemService($this->em);
        $this->sessao = new Session(new MockArraySessionStorage());
    }

    protected function tearDown(): void
    {
        if (isset($this->em)) {
            $conn = $this->em->getConnection();
            $conn->executeStatement('SET search_path TO public');
            $conn->executeStatement('DROP SCHEMA '.$this->schema.' CASCADE');
            $conn->close();
        }
    }

    private function dados(bool $obrigatoria = true): array
    {
        return ['instituicaoId' => $this->admin->getInstituicao()->getId(), 'titulo' => 'Aviso', 'texto' => 'Texto original', 'inicioEm' => gmdate(DATE_ATOM, time()-60), 'fimEm' => gmdate(DATE_ATOM, time()+3600), 'obrigatoria' => $obrigatoria];
    }
    private function criar(bool $obrigatoria = true): array { return $this->service->salvar(null, $this->admin, $this->dados($obrigatoria)); }
    private function liberar(array $m): void
    {
        $this->service->apresentar($m['id'], $this->leitor, $this->sessao);
        $chave = 'mensagem_apresentada_'.$this->leitor->getId();
        $estado = $this->sessao->get($chave); $estado['liberaEm'] = time()-1; $this->sessao->set($chave, $estado);
    }
    public function testAceiteUnicoEConteudoPreservadoAposEdicaoERenovacao(): void
    {
        $m = $this->criar(); $this->liberar($m);
        $this->service->responder($m['id'], $this->leitor, $this->sessao, true, $m['versao']);
        $this->service->responder($m['id'], $this->leitor, $this->sessao, true, $m['versao']);
        $m['texto'] = 'Texto alterado'; $m['fimEm'] = gmdate(DATE_ATOM, time()+7200);
        $this->service->salvar($m['id'], $this->admin, $m);
        self::assertNull($this->service->pendente($this->leitor, $this->sessao));
        $ciencias = $this->service->ciencias($m['id'], $this->admin, 1);
        self::assertSame(1, $ciencias['total']);
        self::assertSame('Texto original', $ciencias['itens'][0]['conteudo']['texto']);
        self::assertNotEmpty($ciencias['itens'][0]['aceitoEm']);
    }
    public function testRecusaAceiteAntesDeTrintaSegundos(): void
    {
        $m = $this->criar(); $r = $this->service->apresentar($m['id'], $this->leitor, $this->sessao);
        self::assertGreaterThanOrEqual(29, $r['restantes']);
        $this->expectException(ConflictHttpException::class);
        $this->service->responder($m['id'], $this->leitor, $this->sessao, true, $m['versao']);
    }
    public function testOpcionalTambemExigeTrintaSegundos(): void
    {
        $m = $this->criar(false); $this->service->apresentar($m['id'], $this->leitor, $this->sessao);
        $this->expectException(ConflictHttpException::class);
        $this->service->responder($m['id'], $this->leitor, $this->sessao, false, $m['versao']);
    }
    public function testObrigatoriaNaoPodeSerDispensada(): void
    {
        $m = $this->criar(); $this->liberar($m);
        $this->expectException(AccessDeniedHttpException::class);
        $this->service->responder($m['id'], $this->leitor, $this->sessao, false, $m['versao']);
    }
    public function testOpcionalDispensadaReapareceEmNovaSessao(): void
    {
        $m = $this->criar(false); $this->liberar($m);
        $this->service->responder($m['id'], $this->leitor, $this->sessao, false, $m['versao']);
        self::assertNull($this->service->pendente($this->leitor, $this->sessao));
        self::assertSame($m['id'], $this->service->pendente($this->leitor, new Session(new MockArraySessionStorage()))?->getId());
        self::assertSame(0, $this->service->ciencias($m['id'], $this->admin, 1)['total']);
    }
    public function testEdicaoDuranteLeituraExigeNovaApresentacao(): void
    {
        $m = $this->criar(); $this->liberar($m);
        $novo = $m; $novo['texto'] = 'Nova versão'; $this->service->salvar($m['id'], $this->admin, $novo);
        $this->expectException(ConflictHttpException::class);
        $this->service->responder($m['id'], $this->leitor, $this->sessao, true, $m['versao']);
    }
    public function testIsolamentoDaFilaEListaAdministrativa(): void
    {
        $this->criar();
        self::assertNull($this->service->pendente($this->outroAdmin, $this->sessao));
        self::assertSame(0, $this->service->listar($this->outroAdmin, 1, '')['total']);
        self::assertSame(1, $this->service->listar($this->admin, 1, '')['total']);
    }
    public function testOutroAdminNaoConsultaCiencias(): void
    {
        $m = $this->criar();
        $this->expectException(NotFoundHttpException::class);
        $this->service->ciencias($m['id'], $this->outroAdmin, 1);
    }
    public function testLeitorNaoCriaMensagem(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->service->salvar(null, $this->leitor, $this->dados());
    }
    public function testExclusaoPreservaCiencias(): void
    {
        $m = $this->criar(); $this->liberar($m);
        $this->service->responder($m['id'], $this->leitor, $this->sessao, true, $m['versao']);
        $this->service->excluir($m['id'], $this->admin);
        self::assertNull($this->service->pendente($this->admin, $this->sessao));
        self::assertSame(1, $this->service->ciencias($m['id'], $this->admin, 1)['total']);
        self::assertTrue($this->service->listar($this->admin, 1, '')['itens'][0]['excluida']);
    }
    public function testVigenciaEOrdemDaFila(): void
    {
        $dados = $this->dados(); $dados['inicioEm'] = gmdate(DATE_ATOM, time()+60);
        $this->service->salvar(null, $this->admin, $dados);
        $dados = $this->dados(); $dados['inicioEm'] = gmdate(DATE_ATOM, time()-3600); $dados['fimEm'] = gmdate(DATE_ATOM, time()-60);
        $this->service->salvar(null, $this->admin, $dados);
        self::assertNull($this->service->pendente($this->leitor, $this->sessao));
        $primeira = $this->criar(false); $segunda = $this->criar();
        self::assertSame($primeira['id'], $this->service->pendente($this->leitor, $this->sessao)?->getId());
        $this->liberar($primeira);
        $this->service->responder($primeira['id'], $this->leitor, $this->sessao, false, $primeira['versao']);
        self::assertSame($segunda['id'], $this->service->pendente($this->leitor, $this->sessao)?->getId());
    }
    public function testRecusaDatasInvalidas(): void
    {
        $dados = $this->dados(); $dados['inicioEm'] = '2026-02-30T12:00:00Z';
        $this->expectException(BadRequestHttpException::class);
        $this->service->salvar(null, $this->admin, $dados);
    }
    public function testBloqueioDosModulosNoBackend(): void
    {
        $this->criar();
        $tokens = $this->createMock(\Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface::class);
        $token = $this->createMock(\Symfony\Component\Security\Core\Authentication\Token\TokenInterface::class);
        $token->method('getUser')->willReturn($this->leitor);
        $tokens->method('getToken')->willReturn($token);
        $subscriber = new \App\Security\MensagensPendentesSubscriber($this->service, $tokens);
        foreach (['/api/v1/dashboard/tipos-conteudo' => 423, '/api/v1/publicacoes/1/arquivo' => 423, '/api/v1/mensagens' => 423, '/api/v1/mensagens/pendente' => 200, '/api/v1/mensagens/1/responder' => 200, '/api/v1/auth/logout' => 200] as $path => $esperado) {
            $request = \Symfony\Component\HttpFoundation\Request::create($path);
            $request->setSession($this->sessao);
            $event = new \Symfony\Component\HttpKernel\Event\ControllerEvent(
                $this->createMock(\Symfony\Component\HttpKernel\HttpKernelInterface::class),
                static fn() => new \Symfony\Component\HttpFoundation\Response(),
                $request,
                \Symfony\Component\HttpKernel\HttpKernelInterface::MAIN_REQUEST,
            );
            $subscriber->verificar($event);
            self::assertSame($esperado, ($event->getController())()->getStatusCode(), $path);
        }
    }

    public function testAdminNaoEditaMensagemDeOutraInstituicao(): void
    {
        $m = $this->criar();
        $this->expectException(NotFoundHttpException::class);
        $this->service->salvar($m['id'], $this->outroAdmin, $m);
    }
    public function testEdicaoDesatualizadaNaoSobrescreveOutraEdicao(): void
    {
        $m = $this->criar();
        $this->service->salvar($m['id'], $this->admin, $m);
        $this->expectException(ConflictHttpException::class);
        $this->service->salvar($m['id'], $this->admin, $m);
    }
    public function testAdminGlobalSelecionaInstituicao(): void
    {
        $this->admin->atualizar($this->admin->getInstituicao(), $this->admin->getEscritorio(), 'Admin', 'admin@example.com', 'ADMIN', true, true);
        $dados = $this->dados(); $dados['instituicaoId'] = $this->outroAdmin->getInstituicao()->getId();
        $m = $this->service->salvar(null, $this->admin, $dados);
        self::assertSame($m['id'], $this->service->pendente($this->outroAdmin, $this->sessao)?->getId());
        self::assertNull($this->service->pendente($this->leitor, $this->sessao));
        self::assertSame(1, $this->service->listar($this->admin, 1, '')['total']);
    }
    public function testSegundoUsuarioPrecisaDeSuaPropriaCiencia(): void
    {
        $m = $this->criar(); $this->liberar($m);
        $this->service->responder($m['id'], $this->leitor, $this->sessao, true, $m['versao']);
        self::assertSame($m['id'], $this->service->pendente($this->admin, $this->sessao)?->getId());
    }
    public function testRecarregarNaoReiniciaContadorDaMesmaVersao(): void
    {
        $m = $this->criar(); $this->liberar($m);
        self::assertSame(0, $this->service->apresentar($m['id'], $this->leitor, $this->sessao)['restantes']);
        $m['texto'] = 'Novo texto';
        $this->service->salvar($m['id'], $this->admin, $m);
        self::assertGreaterThanOrEqual(29, $this->service->apresentar($m['id'], $this->leitor, $this->sessao)['restantes']);
    }

}
