<?php

namespace App\Service\Usuario;

use App\Dto\Usuario\SalvarUsuarioDto;
use App\Dto\Usuario\UsuarioRespostaDto;
use App\Entity\Escritorio;
use App\Entity\RemanejamentoUsuario;
use App\Entity\Usuario;
use App\Repository\EscritorioRepository;
use App\Repository\InstituicaoRepository;
use App\Repository\TipoConteudoRepository;
use App\Repository\UsuarioRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class UsuarioService
{
    public function __construct(private UsuarioRepository $repository, private InstituicaoRepository $instituicaoRepository, private EscritorioRepository $escritorioRepository, private EntityManagerInterface $entityManager, private TipoConteudoRepository $tipoConteudoRepository, private UserPasswordHasherInterface $passwordHasher) {}
    /** @return array{itens: list<array<string, mixed>>, total: int, pagina: int, porPagina: int} */
    public function listar(string $busca, int $pagina, int $porPagina): array { $itens = array_map(static fn(Usuario $item) => UsuarioRespostaDto::daEntidade($item)->paraArray(), $this->repository->listar($busca, $pagina, $porPagina)); return ['itens' => $itens, 'total' => $this->repository->contar($busca), 'pagina' => $pagina, 'porPagina' => $porPagina]; }
    public function criar(SalvarUsuarioDto $dto): UsuarioRespostaDto
    {
        if ($dto->senha === null) throw new BadRequestHttpException('A senha inicial é obrigatória.');
        [$instituicao, $escritorio] = $this->validarVinculo($dto); $this->validarEmail($dto->email);
        $item = new Usuario($instituicao, $escritorio, $dto->nome, $dto->email, '', $dto->perfil, $dto->adminGlobal); $item->alterarSenha($this->passwordHasher->hashPassword($item, $dto->senha)); $this->repository->salvar($item);
        return UsuarioRespostaDto::daEntidade($item);
    }
    public function atualizar(int $id, SalvarUsuarioDto $dto): UsuarioRespostaDto
    {
        $item = $this->obter($id);
        if ($item->getInstituicao()->getId() !== $dto->instituicaoId) throw new BadRequestHttpException('O usuário somente pode ser remanejado entre escritórios da mesma instituição.');
        if ($this->tipoConteudoRepository->existeAtivoPorResponsavel($id) && (!$dto->ativo || $dto->perfil !== Usuario::PERFIL_PUBLICADOR)) throw new ConflictHttpException('Substitua o responsável pelos tipos de conteúdo ativos antes de inativar o usuário ou alterar seu perfil.');
        [$instituicao, $escritorio] = $this->validarVinculo($dto); $this->validarEmail($dto->email, $id);
        $origem = $item->getEscritorio(); $remanejado = $origem->getId() !== $escritorio->getId();
        $this->entityManager->wrapInTransaction(function () use ($item, $dto, $instituicao, $escritorio, $origem, $remanejado): void {
            $item->atualizar($instituicao, $escritorio, $dto->nome, $dto->email, $dto->perfil, $dto->adminGlobal, $dto->ativo);
            if ($dto->senha !== null) $item->alterarSenha($this->passwordHasher->hashPassword($item, $dto->senha));
            if ($remanejado) $this->entityManager->persist(new RemanejamentoUsuario($item, $origem, $escritorio));
            $this->entityManager->flush();
        });
        return UsuarioRespostaDto::daEntidade($item);
    }
    public function excluir(int $id): void { $item = $this->obter($id); if ($this->tipoConteudoRepository->existeAtivoPorResponsavel($id)) throw new ConflictHttpException('Substitua o responsável pelos tipos de conteúdo ativos antes de inativar este usuário.'); $item->inativar(); $this->repository->salvar($item); }
    private function obter(int $id): Usuario { return $this->repository->find($id) ?? throw new NotFoundHttpException('Usuário não encontrado.'); }
    /** @return array{0: \App\Entity\Instituicao, 1: Escritorio} */
    private function validarVinculo(SalvarUsuarioDto $dto): array
    {
        $instituicao = $this->instituicaoRepository->find($dto->instituicaoId) ?? throw new NotFoundHttpException('Instituição não encontrada.');
        $escritorio = $this->escritorioRepository->find($dto->escritorioId) ?? throw new NotFoundHttpException('Escritório não encontrado.');
        if ($escritorio->getInstituicao()->getId() !== $instituicao->getId()) throw new BadRequestHttpException('O escritório não pertence à instituição selecionada.');
        return [$instituicao, $escritorio];
    }
    private function validarEmail(string $email, ?int $ignorarId = null): void { $existente = $this->repository->findOneBy(['email' => mb_strtolower(trim($email))]); if ($existente !== null && $existente->getId() !== $ignorarId) throw new ConflictHttpException('Já existe um usuário com este e-mail.'); }
}
