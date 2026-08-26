<?php

namespace App\Entity;

use App\Repository\ParametroSistemaRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ParametroSistemaRepository::class)]
#[ORM\Table(name: 'parametros_sistema')]
#[ORM\UniqueConstraint(name: 'unq_parametros_chave_escopo', columns: ['chave_escopo'])]
#[ORM\Index(name: 'idx_parametros_instituicao', columns: ['instituicao_id'])]
class ParametroSistema
{
    public const ESCOPO_GLOBAL = 'GLOBAL'; public const ESCOPO_INSTITUICAO = 'INSTITUICAO';
    #[ORM\Id] #[ORM\GeneratedValue] #[ORM\Column] private ?int $id = null;
    #[ORM\ManyToOne] #[ORM\JoinColumn(name: 'instituicao_id', nullable: true, onDelete: 'RESTRICT')] private ?Instituicao $instituicao;
    #[ORM\Column(length: 20)] private string $escopo;
    #[ORM\Column(name: 'chave_escopo', length: 50)] private string $chaveEscopo;
    #[ORM\Column(name: 'limite_upload_mb')] private int $limiteUploadMb = 10;
    #[ORM\Column(name: 'notificacao_interna', options: ['default' => true])] private bool $notificacaoInterna = true;
    #[ORM\Column(name: 'notificacao_email', options: ['default' => true])] private bool $notificacaoEmail = true;
    #[ORM\Column(name: 'antecedencia_expiracao_dias')] private int $antecedenciaExpiracaoDias = 7;
    #[ORM\Column(name: 'fuso_horario', length: 60)] private string $fusoHorario = 'America/Sao_Paulo';
    #[ORM\Column(name: 'criado_em')] private \DateTimeImmutable $criadoEm;
    #[ORM\Column(name: 'atualizado_em')] private \DateTimeImmutable $atualizadoEm;

    public function __construct(?Instituicao $instituicao, int $limiteUploadMb, bool $notificacaoInterna, bool $notificacaoEmail, int $antecedencia, string $fusoHorario)
    {
        $this->instituicao = $instituicao; $this->escopo = $instituicao === null ? self::ESCOPO_GLOBAL : self::ESCOPO_INSTITUICAO;
        $this->chaveEscopo = $instituicao === null ? 'GLOBAL' : 'INSTITUICAO:'.$instituicao->getId(); $this->criadoEm = new \DateTimeImmutable();
        $this->atualizar($limiteUploadMb, $notificacaoInterna, $notificacaoEmail, $antecedencia, $fusoHorario);
    }
    public function atualizar(int $limiteUploadMb, bool $notificacaoInterna, bool $notificacaoEmail, int $antecedencia, string $fusoHorario): void { $this->limiteUploadMb = $limiteUploadMb; $this->notificacaoInterna = $notificacaoInterna; $this->notificacaoEmail = $notificacaoEmail; $this->antecedenciaExpiracaoDias = $antecedencia; $this->fusoHorario = $fusoHorario; $this->atualizadoEm = new \DateTimeImmutable(); }
    /** @return array<string, mixed> */ public function snapshot(): array { return ['limiteUploadMb' => $this->limiteUploadMb, 'notificacaoInterna' => $this->notificacaoInterna, 'notificacaoEmail' => $this->notificacaoEmail, 'antecedenciaExpiracaoDias' => $this->antecedenciaExpiracaoDias, 'fusoHorario' => $this->fusoHorario]; }
    public function getId(): ?int { return $this->id; } public function getInstituicao(): ?Instituicao { return $this->instituicao; } public function getEscopo(): string { return $this->escopo; }
    public function getLimiteUploadMb(): int { return $this->limiteUploadMb; } public function isNotificacaoInterna(): bool { return $this->notificacaoInterna; } public function isNotificacaoEmail(): bool { return $this->notificacaoEmail; }
    public function getAntecedenciaExpiracaoDias(): int { return $this->antecedenciaExpiracaoDias; } public function getFusoHorario(): string { return $this->fusoHorario; }
    public function getCriadoEm(): \DateTimeImmutable { return $this->criadoEm; } public function getAtualizadoEm(): \DateTimeImmutable { return $this->atualizadoEm; }
}
