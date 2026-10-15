<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'ciencias_mensagens')]
#[ORM\Index(name: 'idx_ciencia_mensagem', columns: ['mensagem_id'])]
#[ORM\Index(name: 'idx_ciencia_usuario', columns: ['usuario_id'])]
#[ORM\UniqueConstraint(name: 'unq_ciencia_mensagem_usuario', columns: ['mensagem_id', 'usuario_id'])]
class CienciaMensagem
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column] private ?int $id = null;
    #[ORM\ManyToOne, ORM\JoinColumn(name: 'mensagem_id', nullable: false, onDelete: 'RESTRICT')] private Mensagem $mensagem;
    #[ORM\ManyToOne, ORM\JoinColumn(name: 'usuario_id', nullable: false, onDelete: 'RESTRICT')] private Usuario $usuario;
    #[ORM\Column(name: 'aceito_em', type: 'datetime_immutable')] private \DateTimeImmutable $aceitoEm;
    #[ORM\Column(type: 'json')] private array $conteudo;
    public function __construct(Mensagem $mensagem, Usuario $usuario)
    {
        $this->mensagem = $mensagem; $this->usuario = $usuario;
        $this->aceitoEm = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $this->conteudo = $mensagem->paraArray();
    }
    public function paraArray(): array { return ['usuarioId' => $this->usuario->getId(), 'nome' => $this->usuario->getNome(), 'email' => $this->usuario->getEmail(), 'aceitoEm' => $this->aceitoEm->format(DATE_ATOM), 'conteudo' => $this->conteudo]; }
}
