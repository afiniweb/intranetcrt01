<?php

namespace App\Repository;

use App\Entity\ParametroSistema;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class ParametroSistemaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, ParametroSistema::class); }
    /** @return list<ParametroSistema> */ public function listar(?int $instituicaoId): array { $qb = $this->createQueryBuilder('parametro')->addSelect('instituicao')->leftJoin('parametro.instituicao', 'instituicao'); if ($instituicaoId !== null) $qb->andWhere('IDENTITY(parametro.instituicao) = :instituicao')->setParameter('instituicao', $instituicaoId); return $qb->orderBy('parametro.escopo', 'ASC')->addOrderBy('instituicao.nome', 'ASC')->getQuery()->getResult(); }
}
