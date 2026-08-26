<?php

namespace App\Service\Instituicao;

use App\Dto\Instituicao\InstituicaoRespostaDto;
use App\Dto\Instituicao\SalvarInstituicaoDto;
use App\Entity\Instituicao;
use App\Repository\InstituicaoRepository;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class InstituicaoService
{
    public function __construct(private InstituicaoRepository $repository) {}

    /** @return array{itens: list<array<string, mixed>>, total: int, pagina: int, porPagina: int} */
    public function listar(string $busca, int $pagina, int $porPagina): array
    {
        $itens = array_map(static fn(Instituicao $item) => InstituicaoRespostaDto::daEntidade($item)->paraArray(), $this->repository->listar($busca, $pagina, $porPagina));
        return ['itens' => $itens, 'total' => $this->repository->contar($busca), 'pagina' => $pagina, 'porPagina' => $porPagina];
    }

    public function obter(int $id): Instituicao { return $this->repository->find($id) ?? throw new NotFoundHttpException('Instituição não encontrada.'); }

    public function criar(SalvarInstituicaoDto $dto): InstituicaoRespostaDto
    {
        $this->validarUnicidade($dto);
        $instituicao = new Instituicao($dto->nome, $dto->sigla, $dto->cnpj);
        $this->repository->salvar($instituicao);
        return InstituicaoRespostaDto::daEntidade($instituicao);
    }

    public function atualizar(int $id, SalvarInstituicaoDto $dto): InstituicaoRespostaDto
    {
        $instituicao = $this->obter($id);
        $this->validarUnicidade($dto, $id);
        $instituicao->atualizar($dto->nome, $dto->sigla, $dto->cnpj, $dto->ativo);
        $this->repository->salvar($instituicao);
        return InstituicaoRespostaDto::daEntidade($instituicao);
    }

    public function excluir(int $id): void { $instituicao = $this->obter($id); $instituicao->inativar(); $this->repository->salvar($instituicao); }

    private function validarUnicidade(SalvarInstituicaoDto $dto, ?int $ignorarId = null): void
    {
        foreach (['sigla' => mb_strtoupper(trim($dto->sigla)), 'cnpj' => $dto->cnpj] as $campo => $valor) {
            if ($valor === null) continue;
            $existente = $this->repository->findOneBy([$campo => $valor]);
            if ($existente !== null && $existente->getId() !== $ignorarId) throw new ConflictHttpException(sprintf('Já existe uma instituição com o mesmo %s.', $campo));
        }
    }
}
