<?php

namespace App\Entity;

use App\Repository\UsuarioRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UsuarioRepository::class)]
#[ORM\Table(name: 'usuarios')]
#[ORM\UniqueConstraint(name: 'unq_usuarios_email', columns: ['email'])]
#[ORM\Index(name: 'idx_usuarios_instituicao', columns: ['instituicao_id'])]
#[ORM\Index(name: 'idx_usuarios_escritorio', columns: ['escritorio_id'])]
class Usuario implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const PERFIL_ADMIN = 'ADMIN';
    public const PERFIL_PUBLICADOR = 'PUBLICADOR';

    #[ORM\Id] #[ORM\GeneratedValue] #[ORM\Column] private ?int $id = null;
    #[ORM\ManyToOne] #[ORM\JoinColumn(name: 'instituicao_id', nullable: false, onDelete: 'RESTRICT')] private Instituicao $instituicao;
    #[ORM\ManyToOne] #[ORM\JoinColumn(name: 'escritorio_id', nullable: false, onDelete: 'RESTRICT')] private Escritorio $escritorio;
    #[ORM\Column(length: 150)] private string $nome;
    #[ORM\Column(length: 180)] private string $email;
    #[ORM\Column(name: 'senha_hash', length: 255)] private string $senhaHash;
    #[ORM\Column(length: 20)] private string $perfil;
    #[ORM\Column(name: 'admin_global', options: ['default' => false])] private bool $adminGlobal = false;
    #[ORM\Column(options: ['default' => true])] private bool $ativo = true;
    #[ORM\Column(name: 'criado_em')] private \DateTimeImmutable $criadoEm;
    #[ORM\Column(name: 'atualizado_em')] private \DateTimeImmutable $atualizadoEm;

    public function __construct(Instituicao $instituicao, Escritorio $escritorio, string $nome, string $email, string $senhaHash, string $perfil, bool $adminGlobal)
    {
        $this->criadoEm = new \DateTimeImmutable(); $this->senhaHash = $senhaHash;
        $this->atualizar($instituicao, $escritorio, $nome, $email, $perfil, $adminGlobal, true);
    }
    public function atualizar(Instituicao $instituicao, Escritorio $escritorio, string $nome, string $email, string $perfil, bool $adminGlobal, bool $ativo): void
    {
        $this->instituicao = $instituicao; $this->escritorio = $escritorio; $this->nome = trim($nome);
        $this->email = mb_strtolower(trim($email)); $this->perfil = $perfil;
        $this->adminGlobal = $perfil === self::PERFIL_ADMIN && $adminGlobal; $this->ativo = $ativo; $this->atualizadoEm = new \DateTimeImmutable();
    }
    public function alterarSenha(string $senhaHash): void { $this->senhaHash = $senhaHash; $this->atualizadoEm = new \DateTimeImmutable(); }
    public function inativar(): void { $this->ativo = false; $this->atualizadoEm = new \DateTimeImmutable(); }
    public function getId(): ?int { return $this->id; }
    public function getInstituicao(): Instituicao { return $this->instituicao; }
    public function getEscritorio(): Escritorio { return $this->escritorio; }
    public function getNome(): string { return $this->nome; }
    public function getEmail(): string { return $this->email; }
    public function getSenhaHash(): string { return $this->senhaHash; }
    public function getPassword(): string { return $this->senhaHash; }
    public function getUserIdentifier(): string { return $this->email; }
    public function getRoles(): array { return [$this->perfil === self::PERFIL_ADMIN ? 'ROLE_ADMIN' : 'ROLE_PUBLICADOR', 'ROLE_USER']; }
    public function eraseCredentials(): void {}
    public function getPerfil(): string { return $this->perfil; }
    public function isAdminGlobal(): bool { return $this->adminGlobal; }
    public function isAtivo(): bool { return $this->ativo; }
    public function getCriadoEm(): \DateTimeImmutable { return $this->criadoEm; }
    public function getAtualizadoEm(): \DateTimeImmutable { return $this->atualizadoEm; }
}
