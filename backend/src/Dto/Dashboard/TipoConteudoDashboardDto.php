<?php

namespace App\Dto\Dashboard;

use App\Entity\TipoConteudo;

final readonly class TipoConteudoDashboardDto
{
    public function __construct(public int $id, public string $nome, public ?string $descricao, public string $responsavelNome, public bool $responsavelPeloTipo, public int $totalPublicadas, public ?string $ultimaPublicacaoTitulo, public ?string $ultimaPublicacaoEm) {}
    /** @param array{total: int, titulo: ?string, publicadaEm: ?string} $resumo */
    public static function criar(TipoConteudo $tipo, int $usuarioId, array $resumo): self { return new self($tipo->getId() ?? 0, $tipo->getNome(), $tipo->getDescricao(), $tipo->getResponsavel()->getNome(), $tipo->getResponsavel()->getId() === $usuarioId, $resumo['total'], $resumo['titulo'], $resumo['publicadaEm']); }
    /** @return array<string, mixed> */ public function paraArray(): array { return get_object_vars($this); }
}
