<?php

namespace App\Service\Dashboard;

use App\Dto\Dashboard\PublicacaoDashboardDto;
use App\Dto\Dashboard\TipoConteudoDashboardDto;
use App\Entity\Publicacao;
use App\Entity\TipoConteudo;
use App\Entity\Usuario;
use App\Repository\PublicacaoRepository;
use App\Repository\TipoConteudoRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class DashboardService
{
    public function __construct(private TipoConteudoRepository $tipoRepository, private PublicacaoRepository $publicacaoRepository) {}
    /** @return list<array<string, mixed>> */
    public function listarTipos(Usuario $usuario): array { return array_map(function (TipoConteudo $tipo) use ($usuario): array { $resumo = $this->publicacaoRepository->resumoPublicadoPorTipo($tipo->getId() ?? 0, $usuario->getInstituicao()->getId() ?? 0); return TipoConteudoDashboardDto::criar($tipo, $usuario->getId() ?? 0, $resumo)->paraArray(); }, $this->tipoRepository->listarAtivosPorInstituicao($usuario->getInstituicao()->getId() ?? 0)); }
    /** @return array{itens: list<array<string, mixed>>, total: int, pagina: int, porPagina: int} */
    public function listarPublicacoes(int $tipoId, Usuario $usuario, int $pagina, int $porPagina): array { $tipo = $this->tipoRepository->find($tipoId); if (!$tipo instanceof TipoConteudo || !$tipo->isAtivo() || $tipo->getInstituicao()->getId() !== $usuario->getInstituicao()->getId()) throw new NotFoundHttpException('Tipo de conteúdo não encontrado.'); $itens = array_map(static fn(Publicacao $item) => PublicacaoDashboardDto::daEntidade($item)->paraArray(), $this->publicacaoRepository->listarPublicadasPorTipo($tipoId, $usuario->getInstituicao()->getId() ?? 0, $pagina, $porPagina)); return ['itens' => $itens, 'total' => $this->publicacaoRepository->contarPublicadasPorTipo($tipoId, $usuario->getInstituicao()->getId() ?? 0), 'pagina' => $pagina, 'porPagina' => $porPagina]; }
}
