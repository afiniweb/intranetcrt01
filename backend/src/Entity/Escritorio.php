<?php

namespace App\Entity;

use App\Repository\EscritorioRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EscritorioRepository::class)]
#[ORM\Table(name: 'escritorios')]
#[ORM\Index(name: 'idx_escritorios_instituicao', columns: ['instituicao_id'])]
#[ORM\UniqueConstraint(name: 'unq_escritorios_instituicao_nome', columns: ['instituicao_id', 'nome'])]
class Escritorio
{
    #[ORM\Id] #[ORM\GeneratedValue] #[ORM\Column]
    private ?int $id = null;
    #[ORM\ManyToOne] #[ORM\JoinColumn(name: 'instituicao_id', nullable: false, onDelete: 'RESTRICT')]
    private Instituicao $instituicao;
    #[ORM\Column(length: 150)] private string $nome;
    #[ORM\Column(length: 2)] private string $uf;
    #[ORM\Column(length: 120)] private string $cidade;
    #[ORM\Column(length: 255, nullable: true)] private ?string $endereco = null;
    #[ORM\Column(options: ['default' => true])] private bool $ativo = true;
    #[ORM\Column(name: 'criado_em')] private \DateTimeImmutable $criadoEm;
    #[ORM\Column(name: 'atualizado_em')] private \DateTimeImmutable $atualizadoEm;

    public function __construct(Instituicao $instituicao, string $nome, string $uf, string $cidade, ?string $endereco)
    {
        $this->criadoEm = new \DateTimeImmutable();
        $this->atualizar($instituicao, $nome, $uf, $cidade, $endereco, true);
    }
    public function atualizar(Instituicao $instituicao, string $nome, string $uf, string $cidade, ?string $endereco, bool $ativo): void
    {
        $this->instituicao = $instituicao; $this->nome = trim($nome); $this->uf = mb_strtoupper(trim($uf));
        $this->cidade = trim($cidade); $this->endereco = $endereco !== null && trim($endereco) !== '' ? trim($endereco) : null;
        $this->ativo = $ativo; $this->atualizadoEm = new \DateTimeImmutable();
    }
    public function inativar(): void { $this->ativo = false; $this->atualizadoEm = new \DateTimeImmutable(); }
    public function getId(): ?int { return $this->id; }
    public function getInstituicao(): Instituicao { return $this->instituicao; }
    public function getNome(): string { return $this->nome; }
    public function getUf(): string { return $this->uf; }
    public function getCidade(): string { return $this->cidade; }
    public function getEndereco(): ?string { return $this->endereco; }
    public function isAtivo(): bool { return $this->ativo; }
    public function getCriadoEm(): \DateTimeImmutable { return $this->criadoEm; }
    public function getAtualizadoEm(): \DateTimeImmutable { return $this->atualizadoEm; }
}
