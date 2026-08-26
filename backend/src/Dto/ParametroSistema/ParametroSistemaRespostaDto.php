<?php

namespace App\Dto\ParametroSistema;

use App\Entity\ParametroSistema;

final readonly class ParametroSistemaRespostaDto
{
    public function __construct(public int $id, public string $escopo, public ?int $instituicaoId, public ?string $instituicaoNome, public int $limiteUploadMb, public bool $notificacaoInterna, public bool $notificacaoEmail, public int $antecedenciaExpiracaoDias, public string $fusoHorario, public string $atualizadoEm) {}
    public static function daEntidade(ParametroSistema $item): self { return new self($item->getId() ?? 0, $item->getEscopo(), $item->getInstituicao()?->getId(), $item->getInstituicao()?->getNome(), $item->getLimiteUploadMb(), $item->isNotificacaoInterna(), $item->isNotificacaoEmail(), $item->getAntecedenciaExpiracaoDias(), $item->getFusoHorario(), $item->getAtualizadoEm()->format(DATE_ATOM)); }
    /** @return array<string, mixed> */ public function paraArray(): array { return get_object_vars($this); }
}
