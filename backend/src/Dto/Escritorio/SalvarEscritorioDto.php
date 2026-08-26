<?php

namespace App\Dto\Escritorio;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class SalvarEscritorioDto
{
    public function __construct(
        #[Assert\Positive(message: 'Selecione uma instituição.')] public int $instituicaoId,
        #[Assert\NotBlank(message: 'O nome é obrigatório.')] #[Assert\Length(max: 150)] public string $nome,
        #[Assert\Choice(choices: ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'], message: 'Informe uma UF válida.')] public string $uf,
        #[Assert\NotBlank(message: 'A cidade é obrigatória.')] #[Assert\Length(max: 120)] public string $cidade,
        #[Assert\Length(max: 255)] public ?string $endereco,
        public bool $ativo = true,
    ) {}
    /** @param array<string, mixed> $dados */
    public static function deArray(array $dados): self { return new self((int) ($dados['instituicaoId'] ?? 0), (string) ($dados['nome'] ?? ''), mb_strtoupper((string) ($dados['uf'] ?? '')), (string) ($dados['cidade'] ?? ''), isset($dados['endereco']) ? (string) $dados['endereco'] : null, (bool) ($dados['ativo'] ?? true)); }
}
