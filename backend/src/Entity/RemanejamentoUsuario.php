<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'remanejamentos_usuarios')]
#[ORM\Index(name: 'idx_remanejamentos_usuario', columns: ['usuario_id'])]
#[ORM\Index(name: 'idx_remanejamentos_origem', columns: ['escritorio_origem_id'])]
#[ORM\Index(name: 'idx_remanejamentos_destino', columns: ['escritorio_destino_id'])]
class RemanejamentoUsuario
{
    #[ORM\Id] #[ORM\GeneratedValue] #[ORM\Column] private ?int $id = null;
    #[ORM\ManyToOne] #[ORM\JoinColumn(name: 'usuario_id', nullable: false, onDelete: 'RESTRICT')] private Usuario $usuario;
    #[ORM\ManyToOne] #[ORM\JoinColumn(name: 'escritorio_origem_id', nullable: false, onDelete: 'RESTRICT')] private Escritorio $escritorioOrigem;
    #[ORM\ManyToOne] #[ORM\JoinColumn(name: 'escritorio_destino_id', nullable: false, onDelete: 'RESTRICT')] private Escritorio $escritorioDestino;
    #[ORM\Column(name: 'remanejado_em')] private \DateTimeImmutable $remanejadoEm;
    public function __construct(Usuario $usuario, Escritorio $origem, Escritorio $destino) { $this->usuario = $usuario; $this->escritorioOrigem = $origem; $this->escritorioDestino = $destino; $this->remanejadoEm = new \DateTimeImmutable(); }
}
