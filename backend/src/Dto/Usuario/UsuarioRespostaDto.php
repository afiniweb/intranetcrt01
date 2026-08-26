<?php

namespace App\Dto\Usuario;

use App\Entity\Usuario;

final readonly class UsuarioRespostaDto
{
    public function __construct(public int $id, public int $instituicaoId, public string $instituicaoNome, public int $escritorioId, public string $escritorioNome, public string $nome, public string $email, public string $perfil, public bool $adminGlobal, public bool $ativo, public string $criadoEm, public string $atualizadoEm) {}
    public static function daEntidade(Usuario $item): self { return new self($item->getId() ?? 0, $item->getInstituicao()->getId() ?? 0, $item->getInstituicao()->getNome(), $item->getEscritorio()->getId() ?? 0, $item->getEscritorio()->getNome(), $item->getNome(), $item->getEmail(), $item->getPerfil(), $item->isAdminGlobal(), $item->isAtivo(), $item->getCriadoEm()->format(DATE_ATOM), $item->getAtualizadoEm()->format(DATE_ATOM)); }
    /** @return array<string, mixed> */ public function paraArray(): array { return get_object_vars($this); }
}
