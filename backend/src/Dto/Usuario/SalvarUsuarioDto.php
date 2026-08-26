<?php

namespace App\Dto\Usuario;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class SalvarUsuarioDto
{
    public function __construct(
        #[Assert\Positive(message: 'Selecione uma instituição.')] public int $instituicaoId,
        #[Assert\Positive(message: 'Selecione um escritório.')] public int $escritorioId,
        #[Assert\NotBlank(message: 'O nome é obrigatório.')] #[Assert\Length(max: 150)] public string $nome,
        #[Assert\NotBlank(message: 'O e-mail é obrigatório.')] #[Assert\Email(message: 'Informe um e-mail válido.')] #[Assert\Length(max: 180)] public string $email,
        #[Assert\Choice(choices: ['ADMIN', 'PUBLICADOR'], message: 'Informe um perfil válido.')] public string $perfil,
        public bool $adminGlobal,
        #[Assert\Length(min: 8, max: 72, minMessage: 'A senha deve possuir ao menos 8 caracteres.')] public ?string $senha,
        public bool $ativo = true,
    ) {}
    /** @param array<string, mixed> $dados */
    public static function deArray(array $dados): self { $senha = isset($dados['senha']) && $dados['senha'] !== '' ? (string) $dados['senha'] : null; return new self((int) ($dados['instituicaoId'] ?? 0), (int) ($dados['escritorioId'] ?? 0), (string) ($dados['nome'] ?? ''), (string) ($dados['email'] ?? ''), mb_strtoupper((string) ($dados['perfil'] ?? '')), (bool) ($dados['adminGlobal'] ?? false), $senha, (bool) ($dados['ativo'] ?? true)); }
}
