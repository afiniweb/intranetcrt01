<?php

namespace App\Entity;

use App\Repository\TipoConteudoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TipoConteudoRepository::class)]
#[ORM\Table(name: 'tipos_conteudo')]
#[ORM\UniqueConstraint(name: 'unq_tipos_conteudo_instituicao_nome', columns: ['instituicao_id', 'nome'])]
#[ORM\Index(name: 'idx_tipos_conteudo_instituicao', columns: ['instituicao_id'])]
#[ORM\Index(name: 'idx_tipos_conteudo_responsavel', columns: ['responsavel_id'])]
class TipoConteudo
{
    #[ORM\Id] #[ORM\GeneratedValue] #[ORM\Column] private ?int $id = null;
    #[ORM\ManyToOne] #[ORM\JoinColumn(name: 'instituicao_id', nullable: false, onDelete: 'RESTRICT')] private Instituicao $instituicao;
    #[ORM\ManyToOne] #[ORM\JoinColumn(name: 'responsavel_id', nullable: false, onDelete: 'RESTRICT')] private Usuario $responsavel;
    #[ORM\Column(length: 150)] private string $nome;
    #[ORM\Column(length: 500, nullable: true)] private ?string $descricao = null;
    #[ORM\Column(options: ['default' => true])] private bool $ativo = true;
    #[ORM\Column(name: 'criado_em')] private \DateTimeImmutable $criadoEm;
    #[ORM\Column(name: 'atualizado_em')] private \DateTimeImmutable $atualizadoEm;

    public function __construct(Instituicao $instituicao, Usuario $responsavel, string $nome, ?string $descricao)
    {
        $this->criadoEm = new \DateTimeImmutable(); $this->atualizar($instituicao, $responsavel, $nome, $descricao, true);
    }
    public function atualizar(Instituicao $instituicao, Usuario $responsavel, string $nome, ?string $descricao, bool $ativo): void
    {
        $this->instituicao = $instituicao; $this->responsavel = $responsavel; $this->nome = trim($nome);
        $this->descricao = $descricao !== null && trim($descricao) !== '' ? trim($descricao) : null;
        $this->ativo = $ativo; $this->atualizadoEm = new \DateTimeImmutable();
    }
    public function inativar(): void { $this->ativo = false; $this->atualizadoEm = new \DateTimeImmutable(); }
    public function getId(): ?int { return $this->id; }
    public function getInstituicao(): Instituicao { return $this->instituicao; }
    public function getResponsavel(): Usuario { return $this->responsavel; }
    public function getNome(): string { return $this->nome; }
    public function getDescricao(): ?string { return $this->descricao; }
    public function isAtivo(): bool { return $this->ativo; }
    public function getCriadoEm(): \DateTimeImmutable { return $this->criadoEm; }
    public function getAtualizadoEm(): \DateTimeImmutable { return $this->atualizadoEm; }
}
