<?php

namespace App\Dto\ParametroSistema;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class SalvarParametroSistemaDto
{
    public function __construct(public ?int $instituicaoId, #[Assert\Range(min: 1, max: 100)] public int $limiteUploadMb, public bool $notificacaoInterna, public bool $notificacaoEmail, #[Assert\Range(min: 0, max: 365)] public int $antecedenciaExpiracaoDias, #[Assert\NotBlank] #[Assert\Length(max: 60)] public string $fusoHorario) {}
    /** @param array<string, mixed> $dados */ public static function deArray(array $dados): self { return new self(isset($dados['instituicaoId']) ? (int) $dados['instituicaoId'] : null, (int) ($dados['limiteUploadMb'] ?? 10), (bool) ($dados['notificacaoInterna'] ?? true), (bool) ($dados['notificacaoEmail'] ?? true), (int) ($dados['antecedenciaExpiracaoDias'] ?? 7), (string) ($dados['fusoHorario'] ?? 'America/Sao_Paulo')); }
}
