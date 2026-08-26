<?php

namespace App\Dto\Publicacao;

use App\Entity\Publicacao;

final readonly class PublicacaoRespostaDto
{
    public function __construct(public int $id, public int $tipoConteudoId, public string $tipoConteudo, public string $titulo, public string $corpo, public ?string $anexoUrl, public ?string $arquivoUrl, public string $status, public string $autorNome, public string $instituicaoNome, public string $escritorioNome, public ?string $publicadaEm, public string $criadoEm, public string $atualizadoEm) {}
    public static function daEntidade(Publicacao $item): self { return new self($item->getId() ?? 0, $item->getTipoConteudo()->getId() ?? 0, $item->getTipoConteudo()->getNome(), $item->getTitulo(), $item->getCorpo(), $item->getAnexoUrl(), $item->getArquivoPdf() ? '/api/v1/publicacoes/'.($item->getId() ?? 0).'/arquivo' : null, $item->getStatus(), $item->getAutor()->getNome(), $item->getInstituicao()->getNome(), $item->getEscritorio()->getNome(), $item->getPublicarEm()?->format(DATE_ATOM), $item->getCriadoEm()->format(DATE_ATOM), $item->getAtualizadoEm()->format(DATE_ATOM)); }
    /** @return array<string, mixed> */ public function paraArray(): array { return get_object_vars($this); }
}
