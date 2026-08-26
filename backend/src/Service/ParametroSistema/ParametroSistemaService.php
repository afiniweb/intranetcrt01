<?php

namespace App\Service\ParametroSistema;

use App\Dto\ParametroSistema\ParametroSistemaRespostaDto;
use App\Dto\ParametroSistema\SalvarParametroSistemaDto;
use App\Entity\AuditoriaParametroSistema;
use App\Entity\ParametroSistema;
use App\Entity\Usuario;
use App\Repository\InstituicaoRepository;
use App\Repository\ParametroSistemaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\ForbiddenHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class ParametroSistemaService
{
    public function __construct(private ParametroSistemaRepository $repository, private InstituicaoRepository $instituicaoRepository, private EntityManagerInterface $entityManager) {}
    /** @return list<array<string, mixed>> */ public function listar(Usuario $admin): array { $instituicaoId = $admin->isAdminGlobal() ? null : $admin->getInstituicao()->getId(); return array_map(static fn(ParametroSistema $item) => ParametroSistemaRespostaDto::daEntidade($item)->paraArray(), $this->repository->listar($instituicaoId)); }
    public function criar(Usuario $admin, SalvarParametroSistemaDto $dto): ParametroSistemaRespostaDto
    {
        if ($dto->instituicaoId === null) throw new BadRequestHttpException('Selecione a instituição da configuração.'); $instituicao = $this->instituicaoRepository->find($dto->instituicaoId) ?? throw new NotFoundHttpException('Instituição não encontrada.'); $this->validarEscopoInstitucional($admin, $dto->instituicaoId); $this->validarDados($dto);
        if ($this->repository->findOneBy(['instituicao' => $dto->instituicaoId]) !== null) throw new ConflictHttpException('A instituição já possui parâmetros próprios.');
        $item = new ParametroSistema($instituicao, $dto->limiteUploadMb, $dto->notificacaoInterna, $dto->notificacaoEmail, $dto->antecedenciaExpiracaoDias, $dto->fusoHorario);
        $this->entityManager->persist($item); $this->entityManager->flush(); $this->entityManager->persist(new AuditoriaParametroSistema($item, $admin, null, $item->snapshot())); $this->entityManager->flush();
        return ParametroSistemaRespostaDto::daEntidade($item);
    }
    public function atualizar(int $id, Usuario $admin, SalvarParametroSistemaDto $dto): ParametroSistemaRespostaDto
    {
        $item = $this->repository->find($id) ?? throw new NotFoundHttpException('Parâmetros não encontrados.'); $this->validarAcesso($admin, $item); $this->validarDados($dto); $anteriores = $item->snapshot();
        $item->atualizar($dto->limiteUploadMb, $dto->notificacaoInterna, $dto->notificacaoEmail, $dto->antecedenciaExpiracaoDias, $dto->fusoHorario);
        $this->entityManager->persist(new AuditoriaParametroSistema($item, $admin, $anteriores, $item->snapshot())); $this->entityManager->flush(); return ParametroSistemaRespostaDto::daEntidade($item);
    }
    private function validarAcesso(Usuario $admin, ParametroSistema $item): void { if ($admin->isAdminGlobal()) return; if ($item->getInstituicao()?->getId() !== $admin->getInstituicao()->getId()) throw new ForbiddenHttpException('Parâmetros fora do escopo do administrador.'); }
    private function validarEscopoInstitucional(Usuario $admin, int $instituicaoId): void { if (!$admin->isAdminGlobal() && $admin->getInstituicao()->getId() !== $instituicaoId) throw new ForbiddenHttpException('Instituição fora do escopo do administrador.'); }
    private function validarDados(SalvarParametroSistemaDto $dto): void { if (!in_array($dto->fusoHorario, \DateTimeZone::listIdentifiers(), true)) throw new BadRequestHttpException('Informe um fuso horário IANA válido.'); if (!$dto->notificacaoInterna && !$dto->notificacaoEmail) throw new BadRequestHttpException('Mantenha ao menos um canal de notificação habilitado.'); }
}
