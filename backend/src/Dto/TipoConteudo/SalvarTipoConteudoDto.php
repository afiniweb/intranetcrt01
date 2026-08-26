<?php

namespace App\Dto\TipoConteudo;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class SalvarTipoConteudoDto
{
    public function __construct(
        #[Assert\Positive(message: 'Selecione uma instituição.')] public int $instituicaoId,
        #[Assert\Positive(message: 'Selecione um Publicador responsável.')] public int $responsavelId,
        #[Assert\NotBlank(message: 'O nome é obrigatório.')] #[Assert\Length(max: 150)] public string $nome,
        #[Assert\Length(max: 500)] public ?string $descricao,
        public bool $ativo = true,
    ) {}
    /** @param array<string, mixed> $dados */
    public static function deArray(array $dados): self { return new self((int) ($dados['instituicaoId'] ?? 0), (int) ($dados['responsavelId'] ?? 0), (string) ($dados['nome'] ?? ''), isset($dados['descricao']) ? (string) $dados['descricao'] : null, (bool) ($dados['ativo'] ?? true)); }
}
