<?php

namespace App\Dto\TipoConteudo;

use App\Entity\TipoConteudo;

final readonly class TipoConteudoRespostaDto
{
    public function __construct(public int $id, public int $instituicaoId, public string $instituicaoNome, public int $responsavelId, public string $responsavelNome, public string $nome, public ?string $descricao, public bool $ativo, public string $criadoEm, public string $atualizadoEm) {}
    public static function daEntidade(TipoConteudo $item): self { return new self($item->getId() ?? 0, $item->getInstituicao()->getId() ?? 0, $item->getInstituicao()->getNome(), $item->getResponsavel()->getId() ?? 0, $item->getResponsavel()->getNome(), $item->getNome(), $item->getDescricao(), $item->isAtivo(), $item->getCriadoEm()->format(DATE_ATOM), $item->getAtualizadoEm()->format(DATE_ATOM)); }
    /** @return array<string, mixed> */ public function paraArray(): array { return get_object_vars($this); }
}
