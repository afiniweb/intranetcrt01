<?php

namespace App\Repository;

use App\Entity\Usuario;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

final class UsuarioRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, Usuario::class); }
    /** @return list<Usuario> */
    public function listar(string $busca, int $pagina, int $porPagina): array
    {
        $qb = $this->createQueryBuilder('usuario')->addSelect('instituicao', 'escritorio')->join('usuario.instituicao', 'instituicao')->join('usuario.escritorio', 'escritorio');
        if ($busca !== '') $qb->andWhere('LOWER(usuario.nome) LIKE :busca OR LOWER(usuario.email) LIKE :busca OR LOWER(escritorio.nome) LIKE :busca')->setParameter('busca', '%'.mb_strtolower($busca).'%');
        return $qb->orderBy('usuario.nome', 'ASC')->setFirstResult(($pagina - 1) * $porPagina)->setMaxResults($porPagina)->getQuery()->getResult();
    }
    public function contar(string $busca): int
    {
        $qb = $this->createQueryBuilder('usuario')->select('COUNT(usuario.id)')->join('usuario.escritorio', 'escritorio');
        if ($busca !== '') $qb->andWhere('LOWER(usuario.nome) LIKE :busca OR LOWER(usuario.email) LIKE :busca OR LOWER(escritorio.nome) LIKE :busca')->setParameter('busca', '%'.mb_strtolower($busca).'%');
        return (int) $qb->getQuery()->getSingleScalarResult();
    }
    public function salvar(Usuario $item): void { $this->getEntityManager()->persist($item); $this->getEntityManager()->flush(); }
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof Usuario) throw new UnsupportedUserException(sprintf('Instância de "%s" não suportada.', $user::class));
        $user->alterarSenha($newHashedPassword); $this->salvar($user);
    }
}
