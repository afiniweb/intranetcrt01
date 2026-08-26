<?php

namespace App\Dto\Dashboard;

use App\Entity\Publicacao;

final readonly class PublicacaoDashboardDto
{
    public function __construct(public int $id, public string $titulo, public string $corpo, public ?string $anexoUrl, public ?string $arquivoUrl, public string $autorNome, public string $escritorioNome, public string $publicadaEm) {}
    public static function daEntidade(Publicacao $item): self { return new self($item->getId() ?? 0, $item->getTitulo(), $item->getCorpo(), $item->getAnexoUrl(), $item->getArquivoPdf() ? '/api/v1/publicacoes/'.($item->getId() ?? 0).'/arquivo' : null, $item->getAutor()->getNome(), $item->getEscritorio()->getNome(), ($item->getPublicarEm() ?? $item->getAtualizadoEm())->format(DATE_ATOM)); }
    /** @return array<string, mixed> */ public function paraArray(): array { return get_object_vars($this); }
}
