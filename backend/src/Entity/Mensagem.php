<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'mensagens')]
#[ORM\Index(name: 'idx_mensagens_instituicao', columns: ['instituicao_id'])]
class Mensagem
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column] private ?int $id = null;
    #[ORM\ManyToOne, ORM\JoinColumn(name: 'instituicao_id', nullable: false, onDelete: 'RESTRICT')] private Instituicao $instituicao;
    #[ORM\Column(length: 150)] private string $titulo;
    #[ORM\Column(type: 'text')] private string $texto;
    #[ORM\Column(name: 'inicio_em', type: 'datetime_immutable')] private \DateTimeImmutable $inicioEm;
    #[ORM\Column(name: 'fim_em', type: 'datetime_immutable')] private \DateTimeImmutable $fimEm;
    #[ORM\Column] private bool $obrigatoria;
    #[ORM\Column(options: ['default' => false])] private bool $excluida = false;
    #[ORM\Column(options: ['default' => 1])] private int $versao = 1;
    #[ORM\Column(name: 'criado_em', type: 'datetime_immutable')] private \DateTimeImmutable $criadoEm;
    #[ORM\Column(name: 'atualizado_em', type: 'datetime_immutable')] private \DateTimeImmutable $atualizadoEm;

    public function __construct(Instituicao $instituicao, string $titulo, string $texto, \DateTimeImmutable $inicio, \DateTimeImmutable $fim, bool $obrigatoria)
    {
        $this->instituicao = $instituicao;
        $this->criadoEm = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $this->atualizar($titulo, $texto, $inicio, $fim, $obrigatoria);
        $this->versao = 1;
    }
    public function atualizar(string $titulo, string $texto, \DateTimeImmutable $inicio, \DateTimeImmutable $fim, bool $obrigatoria): void
    {
        $this->titulo = $titulo; $this->texto = $texto; $this->inicioEm = $inicio; $this->fimEm = $fim; $this->obrigatoria = $obrigatoria;
        ++$this->versao;
        $this->atualizadoEm = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }
    public function excluir(): void { $this->excluida = true; ++$this->versao; $this->atualizadoEm = new \DateTimeImmutable('now', new \DateTimeZone('UTC')); }
    public function getId(): ?int { return $this->id; }
    public function getInstituicao(): Instituicao { return $this->instituicao; }
    public function getVersao(): int { return $this->versao; }
    public function isObrigatoria(): bool { return $this->obrigatoria; }
    public function isExcluida(): bool { return $this->excluida; }
    public function vigente(\DateTimeImmutable $agora): bool { return !$this->excluida && $this->inicioEm <= $agora && $agora < $this->fimEm; }
    public function paraArray(): array
    {
        return ['id' => $this->id, 'instituicaoId' => $this->instituicao->getId(), 'instituicaoNome' => $this->instituicao->getNome(), 'titulo' => $this->titulo, 'texto' => $this->texto, 'inicioEm' => $this->inicioEm->format(DATE_ATOM), 'fimEm' => $this->fimEm->format(DATE_ATOM), 'obrigatoria' => $this->obrigatoria, 'excluida' => $this->excluida, 'versao' => $this->versao];
    }
}
