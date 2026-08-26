<?php

namespace App\Entity;

use App\Repository\InstituicaoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InstituicaoRepository::class)]
#[ORM\Table(name: 'instituicoes')]
#[ORM\UniqueConstraint(name: 'unq_instituicoes_sigla', columns: ['sigla'])]
#[ORM\UniqueConstraint(name: 'unq_instituicoes_cnpj', columns: ['cnpj'])]
class Instituicao
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private string $nome;

    #[ORM\Column(length: 20)]
    private string $sigla;

    #[ORM\Column(length: 14, nullable: true)]
    private ?string $cnpj;

    #[ORM\Column(options: ['default' => true])]
    private bool $ativo = true;

    #[ORM\Column(name: 'criado_em')]
    private \DateTimeImmutable $criadoEm;

    #[ORM\Column(name: 'atualizado_em')]
    private \DateTimeImmutable $atualizadoEm;

    public function __construct(string $nome, string $sigla, ?string $cnpj)
    {
        $this->criadoEm = new \DateTimeImmutable();
        $this->atualizar($nome, $sigla, $cnpj, true);
    }

    public function atualizar(string $nome, string $sigla, ?string $cnpj, bool $ativo): void
    {
        $this->nome = trim($nome);
        $this->sigla = mb_strtoupper(trim($sigla));
        $this->cnpj = $cnpj !== null && $cnpj !== '' ? preg_replace('/\D/', '', $cnpj) : null;
        $this->ativo = $ativo;
        $this->atualizadoEm = new \DateTimeImmutable();
    }

    public function inativar(): void { $this->ativo = false; $this->atualizadoEm = new \DateTimeImmutable(); }
    public function getId(): ?int { return $this->id; }
    public function getNome(): string { return $this->nome; }
    public function getSigla(): string { return $this->sigla; }
    public function getCnpj(): ?string { return $this->cnpj; }
    public function isAtivo(): bool { return $this->ativo; }
    public function getCriadoEm(): \DateTimeImmutable { return $this->criadoEm; }
    public function getAtualizadoEm(): \DateTimeImmutable { return $this->atualizadoEm; }
}
