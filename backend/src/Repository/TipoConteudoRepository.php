<?php

namespace App\Repository;

use App\Entity\TipoConteudo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class TipoConteudoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, TipoConteudo::class); }
    /** @return list<TipoConteudo> */
    public function listar(string $busca, int $pagina, int $porPagina): array
    {
        $qb = $this->createQueryBuilder('tipo')->addSelect('instituicao', 'responsavel')->join('tipo.instituicao', 'instituicao')->join('tipo.responsavel', 'responsavel');
        if ($busca !== '') $qb->andWhere('LOWER(tipo.nome) LIKE :busca OR LOWER(tipo.descricao) LIKE :busca OR LOWER(responsavel.nome) LIKE :busca OR LOWER(instituicao.nome) LIKE :busca')->setParameter('busca', '%'.mb_strtolower($busca).'%');
        return $qb->orderBy('tipo.nome', 'ASC')->setFirstResult(($pagina - 1) * $porPagina)->setMaxResults($porPagina)->getQuery()->getResult();
    }
    public function contar(string $busca): int
    {
        $qb = $this->createQueryBuilder('tipo')->select('COUNT(tipo.id)')->join('tipo.instituicao', 'instituicao')->join('tipo.responsavel', 'responsavel');
        if ($busca !== '') $qb->andWhere('LOWER(tipo.nome) LIKE :busca OR LOWER(tipo.descricao) LIKE :busca OR LOWER(responsavel.nome) LIKE :busca OR LOWER(instituicao.nome) LIKE :busca')->setParameter('busca', '%'.mb_strtolower($busca).'%');
        return (int) $qb->getQuery()->getSingleScalarResult();
    }
    public function salvar(TipoConteudo $item): void { $this->getEntityManager()->persist($item); $this->getEntityManager()->flush(); }
    public function existeAtivoPorResponsavel(int $responsavelId): bool { return (int) $this->createQueryBuilder('tipo')->select('COUNT(tipo.id)')->andWhere('IDENTITY(tipo.responsavel) = :id')->andWhere('tipo.ativo = true')->setParameter('id', $responsavelId)->getQuery()->getSingleScalarResult() > 0; }

    /** @return list<TipoConteudo> */
    public function listarAtivosPorInstituicao(int $instituicaoId): array
    {
        return $this->createQueryBuilder('tipo')->addSelect('responsavel')->join('tipo.responsavel', 'responsavel')
            ->andWhere('IDENTITY(tipo.instituicao) = :instituicao')->andWhere('tipo.ativo = true')
            ->setParameter('instituicao', $instituicaoId)->orderBy('tipo.nome', 'ASC')->getQuery()->getResult();
    }
}
