<?php

namespace App\Service\Publicacao;

use App\Dto\Publicacao\PublicacaoRespostaDto;
use App\Dto\Publicacao\SalvarPublicacaoDto;
use App\Entity\Publicacao;
use App\Entity\TransicaoPublicacao;
use App\Entity\Usuario;
use App\Repository\PublicacaoRepository;
use App\Repository\TipoConteudoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class PublicacaoService
{
    public function __construct(private PublicacaoRepository $repository, private TipoConteudoRepository $tipoRepository, private EntityManagerInterface $entityManager, #[Autowire('%kernel.project_dir%')] private string $projectDir) {}
    /** @return array{itens: list<array<string, mixed>>, total: int, pagina: int, porPagina: int} */
    public function listar(Usuario $usuario, string $busca, int $pagina, int $porPagina): array
    {
        $pagina = max(1, $pagina); $porPagina = min(100, max(1, $porPagina));
        $instituicaoId = $usuario->isAdminGlobal() ? null : $usuario->getInstituicao()->getId();
        $responsavelId = $usuario->getPerfil() === Usuario::PERFIL_PUBLICADOR ? $usuario->getId() : null;
        $itens = array_map(static fn (Publicacao $item): array => PublicacaoRespostaDto::daEntidade($item)->paraArray(), $this->repository->listarParaGestao($instituicaoId, $responsavelId, trim($busca), $pagina, $porPagina));
        return ['itens' => $itens, 'total' => $this->repository->contarParaGestao($instituicaoId, $responsavelId, trim($busca)), 'pagina' => $pagina, 'porPagina' => $porPagina];
    }
    public function criar(Usuario $autor, SalvarPublicacaoDto $dto, ?UploadedFile $arquivo): PublicacaoRespostaDto { if ($autor->getPerfil() !== Usuario::PERFIL_PUBLICADOR) throw new AccessDeniedHttpException('Somente Publicadores podem criar conteúdos.'); if (trim($dto->anexoUrl ?? '') === '' && !($arquivo instanceof UploadedFile)) throw new BadRequestHttpException('Informe um link externo, um arquivo PDF ou ambos.'); $tipo = $this->tipoRepository->find($dto->tipoConteudoId) ?? throw new NotFoundHttpException('Tipo de conteúdo não encontrado.'); if (!$tipo->isAtivo() || $tipo->getResponsavel()->getId() !== $autor->getId()) throw new AccessDeniedHttpException('O Publicador não é responsável por este tipo de conteúdo ativo.'); $item = new Publicacao($tipo, $autor, $dto->titulo, $dto->corpo, $dto->anexoUrl); if ($arquivo instanceof UploadedFile) $item->definirArquivoPdf($this->armazenarPdf($arquivo)); $this->entityManager->persist($item); $this->entityManager->persist(new TransicaoPublicacao($item, $autor, Publicacao::RASCUNHO, Publicacao::PUBLICADA)); $this->entityManager->flush(); return PublicacaoRespostaDto::daEntidade($item); }
    public function caminhoArquivo(int $id, Usuario $usuario): string
    {
        $item = $this->repository->find($id) ?? throw new NotFoundHttpException('Publicação não encontrada.');
        if ($item->getStatus() !== Publicacao::PUBLICADA || (!$usuario->isAdminGlobal() && $item->getInstituicao()->getId() !== $usuario->getInstituicao()->getId())) throw new NotFoundHttpException('Arquivo não encontrado.');
        $nome = $item->getArquivoPdf(); if ($nome === null) throw new NotFoundHttpException('Arquivo não encontrado.'); $caminho = $this->diretorioUploads().'/'.$nome;
        if (!is_file($caminho)) throw new NotFoundHttpException('Arquivo não encontrado.'); return $caminho;
    }
    private function armazenarPdf(UploadedFile $arquivo): string
    {
        if (!$arquivo->isValid()) throw new BadRequestHttpException('Não foi possível receber o arquivo.');
        if (($arquivo->getSize() ?: 0) > 10 * 1024 * 1024) throw new BadRequestHttpException('O arquivo PDF deve possuir no máximo 10 MB.');
        $detectorMime = new \finfo(FILEINFO_MIME_TYPE); $mime = $detectorMime->file($arquivo->getPathname());
        if (mb_strtolower($arquivo->getClientOriginalExtension()) !== 'pdf' || $mime !== 'application/pdf') throw new BadRequestHttpException('Envie somente um arquivo PDF válido.');
        $inicio = file_get_contents($arquivo->getPathname(), false, null, 0, 5); if ($inicio !== '%PDF-') throw new BadRequestHttpException('O conteúdo enviado não é um PDF válido.');
        $diretorio = $this->diretorioUploads(); if (!is_dir($diretorio) && !mkdir($diretorio, 0770, true) && !is_dir($diretorio)) throw new \RuntimeException('Não foi possível preparar o armazenamento de arquivos.');
        $nome = bin2hex(random_bytes(24)).'.pdf'; $arquivo->move($diretorio, $nome); return $nome;
    }
    private function diretorioUploads(): string { return $this->projectDir.'/var/uploads/publicacoes'; }
    public function arquivar(int $id, Usuario $usuario): PublicacaoRespostaDto { return $this->alterarDisponibilidade($id, $usuario, false); }
    public function reativar(int $id, Usuario $usuario): PublicacaoRespostaDto { return $this->alterarDisponibilidade($id, $usuario, true); }
    private function alterarDisponibilidade(int $id, Usuario $usuario, bool $reativar): PublicacaoRespostaDto
    {
        $item = $this->repository->find($id) ?? throw new NotFoundHttpException('Publicação não encontrada.'); $this->validarGestao($item, $usuario); $origem = $item->getStatus();
        try { $reativar ? $item->reativar() : $item->arquivar(); } catch (\DomainException $e) { throw new BadRequestHttpException($e->getMessage()); }
        $this->entityManager->persist(new TransicaoPublicacao($item, $usuario, $origem, $item->getStatus())); $this->entityManager->flush(); return PublicacaoRespostaDto::daEntidade($item);
    }
    private function validarGestao(Publicacao $item, Usuario $usuario): void
    {
        if ($usuario->getPerfil() === Usuario::PERFIL_ADMIN) { if (!$usuario->isAdminGlobal() && $item->getInstituicao()->getId() !== $usuario->getInstituicao()->getId()) throw new AccessDeniedHttpException('Publicação fora do escopo institucional do Admin.'); return; }
        if ($item->getInstituicao()->getId() !== $usuario->getInstituicao()->getId() || $item->getTipoConteudo()->getResponsavel()->getId() !== $usuario->getId()) throw new AccessDeniedHttpException('Publicação fora da responsabilidade do usuário.');
    }
}
