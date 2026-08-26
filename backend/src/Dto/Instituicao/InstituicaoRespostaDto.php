<?php

namespace App\Dto\Instituicao;

use App\Entity\Instituicao;

final readonly class InstituicaoRespostaDto
{
    public function __construct(public int $id, public string $nome, public string $sigla, public ?string $cnpj, public bool $ativo, public string $criadoEm, public string $atualizadoEm) {}

    public static function daEntidade(Instituicao $instituicao): self
    {
        return new self($instituicao->getId() ?? 0, $instituicao->getNome(), $instituicao->getSigla(), $instituicao->getCnpj(), $instituicao->isAtivo(), $instituicao->getCriadoEm()->format(DATE_ATOM), $instituicao->getAtualizadoEm()->format(DATE_ATOM));
    }

    /** @return array<string, mixed> */
    public function paraArray(): array { return get_object_vars($this); }
}
