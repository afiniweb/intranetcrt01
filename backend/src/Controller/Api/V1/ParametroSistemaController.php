<?php

namespace App\Controller\Api\V1;

use App\Dto\ParametroSistema\SalvarParametroSistemaDto;
use App\Entity\Usuario;
use App\Service\ParametroSistema\ParametroSistemaService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1/parametros')] #[OA\Tag(name: 'Parâmetros do sistema')]
final class ParametroSistemaController extends AbstractController
{
    public function __construct(private readonly ParametroSistemaService $service, private readonly ValidatorInterface $validator) {}
    #[Route('', methods: ['GET'])] public function listar(): JsonResponse { return new JsonResponse($this->service->listar($this->admin())); }
    #[Route('', methods: ['POST'])] public function criar(Request $request): JsonResponse { return new JsonResponse($this->service->criar($this->admin(), $this->dto($request))->paraArray(), Response::HTTP_CREATED); }
    #[Route('/{id<\d+>}', methods: ['PUT'])] public function atualizar(int $id, Request $request): JsonResponse { return new JsonResponse($this->service->atualizar($id, $this->admin(), $this->dto($request))->paraArray()); }
    private function admin(): Usuario { $usuario = $this->getUser(); if (!$usuario instanceof Usuario) throw $this->createAccessDeniedException(); return $usuario; }
    private function dto(Request $request): SalvarParametroSistemaDto { try { $dados = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR); } catch (\JsonException) { throw new BadRequestHttpException('JSON inválido.'); } $dto = SalvarParametroSistemaDto::deArray(is_array($dados) ? $dados : []); $erros = $this->validator->validate($dto); if (count($erros) > 0) throw new BadRequestHttpException('Confira os limites numéricos e o fuso horário.'); return $dto; }
}
