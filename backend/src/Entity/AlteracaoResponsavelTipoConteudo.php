<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'alteracoes_responsaveis_tipos_conteudo')]
#[ORM\Index(name: 'idx_alteracoes_responsavel_tipo', columns: ['tipo_conteudo_id'])]
#[ORM\Index(name: 'idx_alteracoes_responsavel_anterior', columns: ['responsavel_anterior_id'])]
#[ORM\Index(name: 'idx_alteracoes_responsavel_novo', columns: ['responsavel_novo_id'])]
class AlteracaoResponsavelTipoConteudo
{
    #[ORM\Id] #[ORM\GeneratedValue] #[ORM\Column] private ?int $id = null;
    #[ORM\ManyToOne] #[ORM\JoinColumn(name: 'tipo_conteudo_id', nullable: false, onDelete: 'RESTRICT')] private TipoConteudo $tipoConteudo;
    #[ORM\ManyToOne] #[ORM\JoinColumn(name: 'responsavel_anterior_id', nullable: false, onDelete: 'RESTRICT')] private Usuario $responsavelAnterior;
    #[ORM\ManyToOne] #[ORM\JoinColumn(name: 'responsavel_novo_id', nullable: false, onDelete: 'RESTRICT')] private Usuario $responsavelNovo;
    #[ORM\Column(name: 'alterado_em')] private \DateTimeImmutable $alteradoEm;
    public function __construct(TipoConteudo $tipo, Usuario $anterior, Usuario $novo) { $this->tipoConteudo = $tipo; $this->responsavelAnterior = $anterior; $this->responsavelNovo = $novo; $this->alteradoEm = new \DateTimeImmutable(); }
}
