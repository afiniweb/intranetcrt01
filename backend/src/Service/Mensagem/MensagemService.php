<?php

namespace App\Service\Mensagem;

use App\Entity\{CienciaMensagem, Instituicao, Mensagem, Usuario};
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\{AccessDeniedHttpException, BadRequestHttpException, ConflictHttpException, NotFoundHttpException};

final readonly class MensagemService
{
    public const ESPERA_SEGUNDOS = 30;
    public function __construct(private EntityManagerInterface $em) {}
    private function agora(): \DateTimeImmutable { return new \DateTimeImmutable('now', new \DateTimeZone('UTC')); }
    private function admin(Usuario $usuario): void
    {
        if ($usuario->getPerfil() !== Usuario::PERFIL_ADMIN) throw new AccessDeniedHttpException();
    }
    private function obter(int $id): Mensagem
    {
        return $this->em->find(Mensagem::class, $id) ?? throw new NotFoundHttpException('Mensagem não encontrada.');
    }
    private function escopo(Mensagem $mensagem, Usuario $usuario): void
    {
        $this->admin($usuario);
        if (!$usuario->isAdminGlobal() && $mensagem->getInstituicao()->getId() !== $usuario->getInstituicao()->getId()) throw new NotFoundHttpException('Mensagem não encontrada.');
    }
    public function listar(Usuario $usuario, int $pagina, string $busca): array
    {
        $this->admin($usuario);
        $qb = $this->em->createQueryBuilder()->select('m')->from(Mensagem::class, 'm');
        if (!$usuario->isAdminGlobal()) $qb->andWhere('m.instituicao = :instituicao')->setParameter('instituicao', $usuario->getInstituicao());
        if (trim($busca) !== '') $qb->andWhere('LOWER(m.titulo) LIKE :busca')->setParameter('busca', '%'.mb_strtolower(trim($busca)).'%');
        $total = (int) (clone $qb)->select('COUNT(m.id)')->getQuery()->getSingleScalarResult();
        $itens = $qb->orderBy('m.criadoEm', 'DESC')->addOrderBy('m.id', 'DESC')->setFirstResult((max(1, $pagina)-1)*20)->setMaxResults(20)->getQuery()->getResult();
        return ['itens' => array_map(fn(Mensagem $m) => $m->paraArray(), $itens), 'total' => $total];
    }
    public function salvar(?int $id, Usuario $usuario, array $dados): array
    {
        $this->admin($usuario);
        $titulo = trim(is_string($dados['titulo'] ?? null) ? $dados['titulo'] : '');
        $texto = trim(is_string($dados['texto'] ?? null) ? $dados['texto'] : '');
        if ($titulo === '' || mb_strlen($titulo) > 150 || $texto === '' || mb_strlen($texto) > 20000 || !is_bool($dados['obrigatoria'] ?? null)) throw new BadRequestHttpException('Informe título (até 150 caracteres), texto (até 20.000) e obrigatoriedade.');
        $inicio = $this->data($dados['inicioEm'] ?? null);
        $fim = $this->data($dados['fimEm'] ?? null);
        if ($fim <= $inicio) throw new BadRequestHttpException('O fim deve ser posterior ao início.');
        return $this->em->wrapInTransaction(function () use ($id, $usuario, $dados, $titulo, $texto, $inicio, $fim): array {
            if ($id !== null) {
                $m = $this->obter($id);
                $this->em->refresh($m, LockMode::PESSIMISTIC_WRITE);
                $this->escopo($m, $usuario);
                if ($m->isExcluida()) throw new ConflictHttpException('Mensagem excluída não pode ser editada.');
                if (($dados['versao'] ?? null) !== $m->getVersao()) throw new ConflictHttpException('Mensagem alterada por outro administrador. Recarregue a lista.');
                if ((int) ($dados['instituicaoId'] ?? 0) !== $m->getInstituicao()->getId()) throw new BadRequestHttpException('A instituição não pode ser alterada.');
                $m->atualizar($titulo, $texto, $inicio, $fim, $dados['obrigatoria']);
            } else {
                $instituicaoId = (int) ($dados['instituicaoId'] ?? 0);
                if (!$usuario->isAdminGlobal() && $instituicaoId !== $usuario->getInstituicao()->getId()) throw new AccessDeniedHttpException();
                $instituicao = $this->em->find(Instituicao::class, $instituicaoId);
                if (!$instituicao || !$instituicao->isAtivo()) throw new BadRequestHttpException('Selecione uma instituição ativa.');
                $m = new Mensagem($instituicao, $titulo, $texto, $inicio, $fim, $dados['obrigatoria']);
                $this->em->persist($m);
            }
            $this->em->flush();
            return $m->paraArray();
        });
    }
    private function data(mixed $valor): \DateTimeImmutable
    {
        if (!is_string($valor) || !preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{1,6})?(?:Z|[+-]\d{2}:\d{2})$/', $valor)) throw new BadRequestHttpException('Informe data e hora válidas com fuso horário.');
        try { $data = new \DateTimeImmutable($valor); } catch (\Exception) { throw new BadRequestHttpException('Data inválida.'); }
        $erros = \DateTimeImmutable::getLastErrors();
        if ($erros !== false && ($erros['warning_count'] || $erros['error_count'])) throw new BadRequestHttpException('Data inválida.');
        return $data->setTimezone(new \DateTimeZone('UTC'));
    }
    public function excluir(int $id, Usuario $usuario): void
    {
        $this->em->wrapInTransaction(function () use ($id, $usuario): void {
            $m = $this->obter($id); $this->em->refresh($m, LockMode::PESSIMISTIC_WRITE); $this->escopo($m, $usuario); $m->excluir();
        });
    }
    public function ciencias(int $id, Usuario $usuario, int $pagina): array
    {
        $m = $this->obter($id); $this->escopo($m, $usuario);
        $repo = $this->em->getRepository(CienciaMensagem::class);
        return ['itens' => array_map(fn(CienciaMensagem $c) => $c->paraArray(), $repo->findBy(['mensagem' => $m], ['aceitoEm' => 'DESC', 'id' => 'DESC'], 20, (max(1, $pagina)-1)*20)), 'total' => $repo->count(['mensagem' => $m])];
    }
    public function pendente(Usuario $usuario, SessionInterface $sessao): ?Mensagem
    {
        $qb = $this->em->createQueryBuilder()->select('m')->from(Mensagem::class, 'm')
            ->where('m.instituicao = :instituicao AND m.excluida = false AND m.inicioEm <= :agora AND m.fimEm > :agora')
            ->andWhere('NOT EXISTS (SELECT c.id FROM App\Entity\CienciaMensagem c WHERE c.mensagem = m AND c.usuario = :usuario)')
            ->setParameter('instituicao', $usuario->getInstituicao())->setParameter('usuario', $usuario)->setParameter('agora', $this->agora())
            ->orderBy('m.inicioEm', 'ASC')->addOrderBy('m.id', 'ASC');
        $dispensadas = $sessao->get('mensagens_dispensadas_'.$usuario->getId(), []);
        foreach ($qb->getQuery()->getResult() as $m) {
            if ($m->isObrigatoria() || ($dispensadas[$m->getId()] ?? null) !== $m->getVersao()) return $m;
        }
        return null;
    }
    public function apresentar(int $id, Usuario $usuario, SessionInterface $sessao): array
    {
        $m = $this->pendente($usuario, $sessao);
        if (!$m || $m->getId() !== $id) throw new ConflictHttpException('A fila de mensagens mudou. Atualize a consulta.');
        $chave = 'mensagem_apresentada_'.$usuario->getId();
        $estado = $sessao->get($chave);
        if (!is_array($estado) || $estado['id'] !== $id || $estado['versao'] !== $m->getVersao()) {
            $estado = ['id' => $id, 'versao' => $m->getVersao(), 'liberaEm' => time() + self::ESPERA_SEGUNDOS];
            $sessao->set($chave, $estado);
        }
        return ['mensagem' => $m->paraArray(), 'restantes' => max(0, $estado['liberaEm'] - time())];
    }
    public function responder(int $id, Usuario $usuario, SessionInterface $sessao, bool $aceitar, int $versao): void
    {
        $this->em->wrapInTransaction(function () use ($id, $usuario, $sessao, $aceitar, $versao): void {
            $m = $this->obter($id);
            $this->em->refresh($m, LockMode::PESSIMISTIC_WRITE);
            if ($m->getInstituicao()->getId() !== $usuario->getInstituicao()->getId()) throw new NotFoundHttpException();
            if ($this->em->getRepository(CienciaMensagem::class)->findOneBy(['mensagem' => $m, 'usuario' => $usuario])) return;
            if (!$m->vigente($this->agora())) throw new ConflictHttpException('Mensagem não está mais vigente.');
            $estado = $sessao->get('mensagem_apresentada_'.$usuario->getId(), []);
            if (($estado['id'] ?? null) !== $id || ($estado['versao'] ?? null) !== $m->getVersao() || $versao !== $m->getVersao()) throw new ConflictHttpException('Apresente novamente a versão atual da mensagem.');
            if (time() < $estado['liberaEm']) throw new ConflictHttpException('Aguarde os 30 segundos de leitura.');
            if (!$aceitar && $m->isObrigatoria()) throw new AccessDeniedHttpException('Esta mensagem exige ciência.');
            if ($aceitar) {
                $this->em->persist(new CienciaMensagem($m, $usuario));
                $this->em->flush();
            } else {
                $chave = 'mensagens_dispensadas_'.$usuario->getId();
                $dispensadas = $sessao->get($chave, []); $dispensadas[$id] = $m->getVersao(); $sessao->set($chave, $dispensadas);
            }
        });
    }
}
