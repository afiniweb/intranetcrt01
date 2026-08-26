<?php

namespace App\Dto\Escritorio;

use App\Entity\Escritorio;

final readonly class EscritorioRespostaDto
{
    public function __construct(public int $id, public int $instituicaoId, public string $instituicaoNome, public string $nome, public string $uf, public string $cidade, public ?string $endereco, public bool $ativo, public string $criadoEm, public string $atualizadoEm) {}
    public static function daEntidade(Escritorio $item): self { return new self($item->getId() ?? 0, $item->getInstituicao()->getId() ?? 0, $item->getInstituicao()->getNome(), $item->getNome(), $item->getUf(), $item->getCidade(), $item->getEndereco(), $item->isAtivo(), $item->getCriadoEm()->format(DATE_ATOM), $item->getAtualizadoEm()->format(DATE_ATOM)); }
    /** @return array<string, mixed> */ public function paraArray(): array { return get_object_vars($this); }
}
