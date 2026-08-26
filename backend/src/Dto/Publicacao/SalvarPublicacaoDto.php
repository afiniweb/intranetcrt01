<?php

namespace App\Dto\Publicacao;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class SalvarPublicacaoDto
{
    public function __construct(#[Assert\Positive] public int $tipoConteudoId, #[Assert\NotBlank] #[Assert\Length(max: 150)] public string $titulo, #[Assert\NotBlank] public string $corpo, #[Assert\Length(max: 500)] public ?string $anexoUrl) {}
    /** @param array<string, mixed> $dados */ public static function deArray(array $dados): self { return new self((int) ($dados['tipoConteudoId'] ?? 0), (string) ($dados['titulo'] ?? ''), (string) ($dados['corpo'] ?? ''), isset($dados['anexoUrl']) ? (string) $dados['anexoUrl'] : null); }
}
