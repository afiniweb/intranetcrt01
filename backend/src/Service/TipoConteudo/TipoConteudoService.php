<?php

namespace App\Service\TipoConteudo;

use App\Dto\TipoConteudo\SalvarTipoConteudoDto;
use App\Dto\TipoConteudo\TipoConteudoRespostaDto;
use App\Entity\AlteracaoResponsavelTipoConteudo;
use App\Entity\Instituicao;
use App\Entity\TipoConteudo;
use App\Entity\Usuario;
use App\Repository\InstituicaoRepository;
use App\Repository\TipoConteudoRepository;
use App\Repository\UsuarioRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class TipoConteudoService
{
    public function __construct(private TipoConteudoRepository $repository, private InstituicaoRepository $instituicaoRepository, private UsuarioRepository $usuarioRepository, private EntityManagerInterface $entityManager) {}
    /** @return array{itens: list<array<string, mixed>>, total: int, pagina: int, porPagina: int} */
    public function listar(string $busca, int $pagina, int $porPagina): array { $itens = array_map(static fn(TipoConteudo $item) => TipoConteudoRespostaDto::daEntidade($item)->paraArray(), $this->repository->listar($busca, $pagina, $porPagina)); return ['itens' => $itens, 'total' => $this->repository->contar($busca), 'pagina' => $pagina, 'porPagina' => $porPagina]; }
    public function criar(SalvarTipoConteudoDto $dto): TipoConteudoRespostaDto
    {
        [$instituicao, $responsavel] = $this->validarVinculos($dto); $this->validarUnicidade($dto);
        $item = new TipoConteudo($instituicao, $responsavel, $dto->nome, $dto->descricao); $this->repository->salvar($item);
        return TipoConteudoRespostaDto::daEntidade($item);
    }
    public function atualizar(int $id, SalvarTipoConteudoDto $dto): TipoConteudoRespostaDto
    {
        $item = $this->obter($id); [$instituicao, $responsavel] = $this->validarVinculos($dto); $this->validarUnicidade($dto, $id);
        $anterior = $item->getResponsavel(); $responsavelAlterado = $anterior->getId() !== $responsavel->getId();
        $this->entityManager->wrapInTransaction(function () use ($item, $dto, $instituicao, $responsavel, $anterior, $responsavelAlterado): void {
            $item->atualizar($instituicao, $responsavel, $dto->nome, $dto->descricao, $dto->ativo);
            if ($responsavelAlterado) $this->entityManager->persist(new AlteracaoResponsavelTipoConteudo($item, $anterior, $responsavel));
            $this->entityManager->flush();
        });
        return TipoConteudoRespostaDto::daEntidade($item);
    }
    public function excluir(int $id): void { $item = $this->obter($id); $item->inativar(); $this->repository->salvar($item); }
    private function obter(int $id): TipoConteudo { return $this->repository->find($id) ?? throw new NotFoundHttpException('Tipo de conteúdo não encontrado.'); }
    /** @return array{0: Instituicao, 1: Usuario} */
    private function validarVinculos(SalvarTipoConteudoDto $dto): array
    {
        $instituicao = $this->instituicaoRepository->find($dto->instituicaoId) ?? throw new NotFoundHttpException('Instituição não encontrada.');
        $responsavel = $this->usuarioRepository->find($dto->responsavelId) ?? throw new NotFoundHttpException('Publicador responsável não encontrado.');
        if (!$responsavel->isAtivo() || $responsavel->getPerfil() !== Usuario::PERFIL_PUBLICADOR) throw new BadRequestHttpException('O responsável deve ser um Publicador ativo.');
        if ($responsavel->getInstituicao()->getId() !== $instituicao->getId()) throw new BadRequestHttpException('O responsável não pertence à instituição selecionada.');
        return [$instituicao, $responsavel];
    }
    private function validarUnicidade(SalvarTipoConteudoDto $dto, ?int $ignorarId = null): void { $existente = $this->repository->findOneBy(['instituicao' => $dto->instituicaoId, 'nome' => trim($dto->nome)]); if ($existente !== null && $existente->getId() !== $ignorarId) throw new ConflictHttpException('Já existe um tipo de conteúdo com esse nome na instituição.'); }
}
