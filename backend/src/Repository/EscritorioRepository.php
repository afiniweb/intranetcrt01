<?php

namespace App\Repository;

use App\Entity\Escritorio;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class EscritorioRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, Escritorio::class); }
    /** @return list<Escritorio> */
    public function listar(string $busca, int $pagina, int $porPagina): array
    {
        $qb = $this->createQueryBuilder('escritorio')->addSelect('instituicao')->join('escritorio.instituicao', 'instituicao');
        if ($busca !== '') $qb->andWhere('LOWER(escritorio.nome) LIKE :busca OR LOWER(escritorio.cidade) LIKE :busca OR LOWER(instituicao.nome) LIKE :busca')->setParameter('busca', '%'.mb_strtolower($busca).'%');
        return $qb->orderBy('escritorio.nome', 'ASC')->setFirstResult(($pagina - 1) * $porPagina)->setMaxResults($porPagina)->getQuery()->getResult();
    }
    public function contar(string $busca): int
    {
        $qb = $this->createQueryBuilder('escritorio')->select('COUNT(escritorio.id)')->join('escritorio.instituicao', 'instituicao');
        if ($busca !== '') $qb->andWhere('LOWER(escritorio.nome) LIKE :busca OR LOWER(escritorio.cidade) LIKE :busca OR LOWER(instituicao.nome) LIKE :busca')->setParameter('busca', '%'.mb_strtolower($busca).'%');
        return (int) $qb->getQuery()->getSingleScalarResult();
    }
    public function salvar(Escritorio $item): void { $this->getEntityManager()->persist($item); $this->getEntityManager()->flush(); }
}
