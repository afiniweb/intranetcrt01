<?php

namespace App\Repository;

use App\Entity\Publicacao;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class PublicacaoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, Publicacao::class); }
    /** @return list<Publicacao> */
    public function listarParaGestao(?int $instituicaoId, ?int $responsavelId, string $busca, int $pagina, int $porPagina): array
    {
        return $this->consultaGestao($instituicaoId, $responsavelId, $busca)->addSelect('tipo', 'autor', 'instituicao', 'escritorio')->join('publicacao.tipoConteudo', 'tipo')->join('publicacao.autor', 'autor')->join('publicacao.instituicao', 'instituicao')->join('publicacao.escritorio', 'escritorio')->orderBy('publicacao.atualizadoEm', 'DESC')->setFirstResult(($pagina - 1) * $porPagina)->setMaxResults($porPagina)->getQuery()->getResult();
    }
    public function contarParaGestao(?int $instituicaoId, ?int $responsavelId, string $busca): int { return (int) $this->consultaGestao($instituicaoId, $responsavelId, $busca)->select('COUNT(publicacao.id)')->getQuery()->getSingleScalarResult(); }
    private function consultaGestao(?int $instituicaoId, ?int $responsavelId, string $busca): \Doctrine\ORM\QueryBuilder
    {
        $qb = $this->createQueryBuilder('publicacao');
        if ($instituicaoId !== null) $qb->andWhere('IDENTITY(publicacao.instituicao) = :instituicao')->setParameter('instituicao', $instituicaoId);
        if ($responsavelId !== null) $qb->join('publicacao.tipoConteudo', 'tipo_filtro')->andWhere('IDENTITY(tipo_filtro.responsavel) = :responsavel')->setParameter('responsavel', $responsavelId);
        if ($busca !== '') $qb->andWhere('LOWER(publicacao.titulo) LIKE :busca')->setParameter('busca', '%'.mb_strtolower($busca).'%');
        return $qb;
    }

    /** @return array{total: int, titulo: ?string, publicadaEm: ?string} */
    public function resumoPublicadoPorTipo(int $tipoId, int $instituicaoId): array
    {
        $qb = $this->createQueryBuilder('publicacao')->andWhere('IDENTITY(publicacao.tipoConteudo) = :tipo')->andWhere('IDENTITY(publicacao.instituicao) = :instituicao')->andWhere('publicacao.status = :status')->setParameter('tipo', $tipoId)->setParameter('instituicao', $instituicaoId)->setParameter('status', Publicacao::PUBLICADA);
        $total = (int) (clone $qb)->select('COUNT(publicacao.id)')->getQuery()->getSingleScalarResult();
        $ultima = $qb->orderBy('publicacao.publicarEm', 'DESC')->addOrderBy('publicacao.atualizadoEm', 'DESC')->setMaxResults(1)->getQuery()->getOneOrNullResult();
        return ['total' => $total, 'titulo' => $ultima?->getTitulo(), 'publicadaEm' => $ultima ? ($ultima->getPublicarEm() ?? $ultima->getAtualizadoEm())->format(DATE_ATOM) : null];
    }

    /** @return list<Publicacao> */
    public function listarPublicadasPorTipo(int $tipoId, int $instituicaoId, int $pagina, int $porPagina): array
    {
        return $this->consultaPublicadasPorTipo($tipoId, $instituicaoId)->addSelect('autor', 'escritorio')->join('publicacao.autor', 'autor')->join('publicacao.escritorio', 'escritorio')->orderBy('publicacao.publicarEm', 'DESC')->addOrderBy('publicacao.atualizadoEm', 'DESC')->setFirstResult(($pagina - 1) * $porPagina)->setMaxResults($porPagina)->getQuery()->getResult();
    }
    public function contarPublicadasPorTipo(int $tipoId, int $instituicaoId): int { return (int) $this->consultaPublicadasPorTipo($tipoId, $instituicaoId)->select('COUNT(publicacao.id)')->getQuery()->getSingleScalarResult(); }
    private function consultaPublicadasPorTipo(int $tipoId, int $instituicaoId): \Doctrine\ORM\QueryBuilder { return $this->createQueryBuilder('publicacao')->andWhere('IDENTITY(publicacao.tipoConteudo) = :tipo')->andWhere('IDENTITY(publicacao.instituicao) = :instituicao')->andWhere('publicacao.status = :status')->setParameter('tipo', $tipoId)->setParameter('instituicao', $instituicaoId)->setParameter('status', Publicacao::PUBLICADA); }
}
