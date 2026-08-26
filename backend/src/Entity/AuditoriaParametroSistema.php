<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity] #[ORM\Table(name: 'auditorias_parametros_sistema')] #[ORM\Index(name: 'idx_auditorias_parametro', columns: ['parametro_id'])] #[ORM\Index(name: 'idx_auditorias_parametro_usuario', columns: ['usuario_id'])]
class AuditoriaParametroSistema
{
    #[ORM\Id] #[ORM\GeneratedValue] #[ORM\Column] private ?int $id = null;
    #[ORM\ManyToOne] #[ORM\JoinColumn(name: 'parametro_id', nullable: false, onDelete: 'RESTRICT')] private ParametroSistema $parametro;
    #[ORM\ManyToOne] #[ORM\JoinColumn(name: 'usuario_id', nullable: false, onDelete: 'RESTRICT')] private Usuario $usuario;
    #[ORM\Column(name: 'valores_anteriores', type: Types::JSON, nullable: true)] private ?array $valoresAnteriores;
    #[ORM\Column(name: 'valores_novos', type: Types::JSON)] private array $valoresNovos;
    #[ORM\Column(name: 'alterado_em')] private \DateTimeImmutable $alteradoEm;
    /** @param array<string, mixed>|null $anteriores @param array<string, mixed> $novos */ public function __construct(ParametroSistema $parametro, Usuario $usuario, ?array $anteriores, array $novos) { $this->parametro = $parametro; $this->usuario = $usuario; $this->valoresAnteriores = $anteriores; $this->valoresNovos = $novos; $this->alteradoEm = new \DateTimeImmutable(); }
}
