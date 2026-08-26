<?php

namespace App\Dto\Instituicao;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class SalvarInstituicaoDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'O nome é obrigatório.')]
        #[Assert\Length(max: 150, maxMessage: 'O nome deve possuir no máximo 150 caracteres.')]
        public string $nome,
        #[Assert\NotBlank(message: 'A sigla é obrigatória.')]
        #[Assert\Length(max: 20, maxMessage: 'A sigla deve possuir no máximo 20 caracteres.')]
        public string $sigla,
        #[Assert\Regex(pattern: '/^\d{14}$/', message: 'O CNPJ deve possuir 14 números.', match: true)]
        public ?string $cnpj,
        public bool $ativo = true,
    ) {}

    /** @param array<string, mixed> $dados */
    public static function deArray(array $dados): self
    {
        $cnpj = isset($dados['cnpj']) ? preg_replace('/\D/', '', (string) $dados['cnpj']) : null;
        return new self((string) ($dados['nome'] ?? ''), (string) ($dados['sigla'] ?? ''), $cnpj ?: null, (bool) ($dados['ativo'] ?? true));
    }
}
