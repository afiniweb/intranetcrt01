<?php

namespace App\Entity;

use App\Repository\PublicacaoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PublicacaoRepository::class)]
#[ORM\Table(name: 'publicacoes')]
#[ORM\Index(name: 'idx_publicacoes_status', columns: ['status'])]
#[ORM\Index(name: 'idx_publicacoes_instituicao', columns: ['instituicao_id'])]
#[ORM\Index(name: 'idx_publicacoes_tipo', columns: ['tipo_conteudo_id'])]
#[ORM\Index(name: 'idx_publicacoes_autor', columns: ['autor_id'])]
#[ORM\Index(name: 'idx_publicacoes_escritorio', columns: ['escritorio_id'])]
#[ORM\Index(name: 'idx_publicacoes_aprovador', columns: ['aprovador_id'])]
class Publicacao
{
    public const RASCUNHO = 'RASCUNHO'; public const AGUARDANDO_APROVACAO = 'AGUARDANDO_APROVACAO';
    public const APROVADA_AGENDADA = 'APROVADA_AGENDADA'; public const PUBLICADA = 'PUBLICADA';
    public const EXPIRADA = 'EXPIRADA'; public const ARQUIVADA = 'ARQUIVADA'; public const REJEITADA = 'REJEITADA';

    #[ORM\Id] #[ORM\GeneratedValue] #[ORM\Column] private ?int $id = null;
    #[ORM\ManyToOne] #[ORM\JoinColumn(name: 'tipo_conteudo_id', nullable: false, onDelete: 'RESTRICT')] private TipoConteudo $tipoConteudo;
    #[ORM\ManyToOne] #[ORM\JoinColumn(name: 'autor_id', nullable: false, onDelete: 'RESTRICT')] private Usuario $autor;
    #[ORM\ManyToOne] #[ORM\JoinColumn(name: 'instituicao_id', nullable: false, onDelete: 'RESTRICT')] private Instituicao $instituicao;
    #[ORM\ManyToOne] #[ORM\JoinColumn(name: 'escritorio_id', nullable: false, onDelete: 'RESTRICT')] private Escritorio $escritorio;
    #[ORM\ManyToOne] #[ORM\JoinColumn(name: 'aprovador_id', nullable: true, onDelete: 'RESTRICT')] private ?Usuario $aprovador = null;
    #[ORM\Column(length: 150)] private string $titulo;
    #[ORM\Column(type: 'text')] private string $corpo;
    #[ORM\Column(name: 'anexo_url', length: 500, nullable: true)] private ?string $anexoUrl = null;
    #[ORM\Column(name: 'arquivo_pdf', length: 255, nullable: true)] private ?string $arquivoPdf = null;
    #[ORM\Column(length: 30)] private string $status = self::PUBLICADA;
    #[ORM\Column(name: 'justificativa_rejeicao', length: 1000, nullable: true)] private ?string $justificativaRejeicao = null;
    #[ORM\Column(name: 'publicar_em', nullable: true)] private ?\DateTimeImmutable $publicarEm = null;
    #[ORM\Column(name: 'expirar_em', nullable: true)] private ?\DateTimeImmutable $expirarEm = null;
    #[ORM\Column(name: 'criado_em')] private \DateTimeImmutable $criadoEm;
    #[ORM\Column(name: 'atualizado_em')] private \DateTimeImmutable $atualizadoEm;

    public function __construct(TipoConteudo $tipo, Usuario $autor, string $titulo, string $corpo, ?string $anexoUrl)
    {
        $this->tipoConteudo = $tipo; $this->autor = $autor; $this->instituicao = $autor->getInstituicao(); $this->escritorio = $autor->getEscritorio();
        $this->titulo = trim($titulo); $this->corpo = trim($corpo); $this->anexoUrl = $anexoUrl !== null && trim($anexoUrl) !== '' ? trim($anexoUrl) : null;
        $this->criadoEm = $this->atualizadoEm = $this->publicarEm = new \DateTimeImmutable();
    }
    public function arquivar(): void { if ($this->status !== self::PUBLICADA) throw new \DomainException('Somente uma publicação ativa pode ser desabilitada.'); $this->status = self::ARQUIVADA; $this->atualizadoEm = new \DateTimeImmutable(); }
    public function reativar(): void { if ($this->status !== self::ARQUIVADA) throw new \DomainException('Somente uma publicação desabilitada pode ser reativada.'); $this->status = self::PUBLICADA; $this->publicarEm = new \DateTimeImmutable(); $this->expirarEm = null; $this->atualizadoEm = $this->publicarEm; }
    public function getId(): ?int { return $this->id; } public function getTipoConteudo(): TipoConteudo { return $this->tipoConteudo; }
    public function getAutor(): Usuario { return $this->autor; } public function getInstituicao(): Instituicao { return $this->instituicao; }
    public function getEscritorio(): Escritorio { return $this->escritorio; } public function getTitulo(): string { return $this->titulo; }
    public function getCorpo(): string { return $this->corpo; } public function getAnexoUrl(): ?string { return $this->anexoUrl; }
    public function definirArquivoPdf(string $arquivoPdf): void { $this->arquivoPdf = $arquivoPdf; $this->atualizadoEm = new \DateTimeImmutable(); }
    public function getArquivoPdf(): ?string { return $this->arquivoPdf; }
    public function getStatus(): string { return $this->status; } public function getPublicarEm(): ?\DateTimeImmutable { return $this->publicarEm; }
    public function getExpirarEm(): ?\DateTimeImmutable { return $this->expirarEm; } public function getCriadoEm(): \DateTimeImmutable { return $this->criadoEm; }
    public function getAtualizadoEm(): \DateTimeImmutable { return $this->atualizadoEm; }
}
