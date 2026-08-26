<?php

namespace App\Repository;

use App\Entity\Instituicao;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class InstituicaoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, Instituicao::class); }

    /** @return list<Instituicao> */
    public function listar(string $busca, int $pagina, int $porPagina): array
    {
        $qb = $this->createQueryBuilder('instituicao');
        if ($busca !== '') {
            $qb->andWhere('LOWER(instituicao.nome) LIKE :busca OR LOWER(instituicao.sigla) LIKE :busca')
                ->setParameter('busca', '%'.mb_strtolower($busca).'%');
        }
        return $qb->orderBy('instituicao.nome', 'ASC')->setFirstResult(($pagina - 1) * $porPagina)->setMaxResults($porPagina)->getQuery()->getResult();
    }

    public function contar(string $busca): int
    {
        $qb = $this->createQueryBuilder('instituicao')->select('COUNT(instituicao.id)');
        if ($busca !== '') {
            $qb->andWhere('LOWER(instituicao.nome) LIKE :busca OR LOWER(instituicao.sigla) LIKE :busca')
                ->setParameter('busca', '%'.mb_strtolower($busca).'%');
        }
        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function salvar(Instituicao $instituicao): void { $this->getEntityManager()->persist($instituicao); $this->getEntityManager()->flush(); }
}
