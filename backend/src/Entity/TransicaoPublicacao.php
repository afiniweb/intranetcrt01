<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity] #[ORM\Table(name: 'transicoes_publicacoes')] #[ORM\Index(name: 'idx_transicoes_publicacao', columns: ['publicacao_id'])] #[ORM\Index(name: 'idx_transicoes_usuario', columns: ['usuario_id'])]
class TransicaoPublicacao
{
    #[ORM\Id] #[ORM\GeneratedValue] #[ORM\Column] private ?int $id = null;
    #[ORM\ManyToOne] #[ORM\JoinColumn(name: 'publicacao_id', nullable: false, onDelete: 'RESTRICT')] private Publicacao $publicacao;
    #[ORM\ManyToOne] #[ORM\JoinColumn(name: 'usuario_id', nullable: false, onDelete: 'RESTRICT')] private Usuario $usuario;
    #[ORM\Column(name: 'status_origem', length: 30)] private string $statusOrigem;
    #[ORM\Column(name: 'status_destino', length: 30)] private string $statusDestino;
    #[ORM\Column(length: 1000, nullable: true)] private ?string $justificativa;
    #[ORM\Column(name: 'ocorrido_em')] private \DateTimeImmutable $ocorridoEm;
    public function __construct(Publicacao $publicacao, Usuario $usuario, string $origem, string $destino, ?string $justificativa = null) { $this->publicacao = $publicacao; $this->usuario = $usuario; $this->statusOrigem = $origem; $this->statusDestino = $destino; $this->justificativa = $justificativa; $this->ocorridoEm = new \DateTimeImmutable(); }
}
