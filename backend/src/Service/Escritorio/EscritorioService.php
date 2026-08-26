<?php

namespace App\Service\Escritorio;

use App\Dto\Escritorio\EscritorioRespostaDto;
use App\Dto\Escritorio\SalvarEscritorioDto;
use App\Entity\Escritorio;
use App\Entity\Instituicao;
use App\Repository\EscritorioRepository;
use App\Repository\InstituicaoRepository;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class EscritorioService
{
    public function __construct(private EscritorioRepository $repository, private InstituicaoRepository $instituicaoRepository) {}
    /** @return array{itens: list<array<string, mixed>>, total: int, pagina: int, porPagina: int} */
    public function listar(string $busca, int $pagina, int $porPagina): array { $itens = array_map(static fn(Escritorio $item) => EscritorioRespostaDto::daEntidade($item)->paraArray(), $this->repository->listar($busca, $pagina, $porPagina)); return ['itens' => $itens, 'total' => $this->repository->contar($busca), 'pagina' => $pagina, 'porPagina' => $porPagina]; }
    public function criar(SalvarEscritorioDto $dto): EscritorioRespostaDto { $this->validarUnicidade($dto); $item = new Escritorio($this->obterInstituicao($dto->instituicaoId), $dto->nome, $dto->uf, $dto->cidade, $dto->endereco); $this->repository->salvar($item); return EscritorioRespostaDto::daEntidade($item); }
    public function atualizar(int $id, SalvarEscritorioDto $dto): EscritorioRespostaDto { $item = $this->obter($id); $this->validarUnicidade($dto, $id); $item->atualizar($this->obterInstituicao($dto->instituicaoId), $dto->nome, $dto->uf, $dto->cidade, $dto->endereco, $dto->ativo); $this->repository->salvar($item); return EscritorioRespostaDto::daEntidade($item); }
    public function excluir(int $id): void { $item = $this->obter($id); $item->inativar(); $this->repository->salvar($item); }
    private function obter(int $id): Escritorio { return $this->repository->find($id) ?? throw new NotFoundHttpException('Escritório não encontrado.'); }
    private function obterInstituicao(int $id): Instituicao { return $this->instituicaoRepository->find($id) ?? throw new NotFoundHttpException('Instituição não encontrada.'); }
    private function validarUnicidade(SalvarEscritorioDto $dto, ?int $ignorarId = null): void { $existente = $this->repository->findOneBy(['instituicao' => $dto->instituicaoId, 'nome' => trim($dto->nome)]); if ($existente !== null && $existente->getId() !== $ignorarId) throw new ConflictHttpException('Já existe um escritório com esse nome na instituição.'); }
}
