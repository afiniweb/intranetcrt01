<?php

namespace App\Controller\Api\V1;

use App\Dto\Usuario\SalvarUsuarioDto;
use App\Service\Usuario\UsuarioService;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1/usuarios')] #[OA\Tag(name: 'Usuários')]
final readonly class UsuarioController
{
    public function __construct(private UsuarioService $service, private ValidatorInterface $validator) {}
    #[Route('', methods: ['GET'])] public function listar(Request $request): JsonResponse { $pagina = max(1, $request->query->getInt('pagina', 1)); $porPagina = min(100, max(1, $request->query->getInt('porPagina', 10))); return new JsonResponse($this->service->listar(trim((string) $request->query->get('busca', '')), $pagina, $porPagina)); }
    #[Route('', methods: ['POST'])] public function criar(Request $request): JsonResponse { return new JsonResponse($this->service->criar($this->dto($request))->paraArray(), Response::HTTP_CREATED); }
    #[Route('/{id<\d+>}', methods: ['PUT'])] public function atualizar(int $id, Request $request): JsonResponse { return new JsonResponse($this->service->atualizar($id, $this->dto($request))->paraArray()); }
    #[Route('/{id<\d+>}', methods: ['DELETE'])] public function excluir(int $id): Response { $this->service->excluir($id); return new Response(status: Response::HTTP_NO_CONTENT); }
    private function dto(Request $request): SalvarUsuarioDto
    {
        try { $dados = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR); } catch (\JsonException) { throw new BadRequestHttpException('JSON inválido.'); }
        $dto = SalvarUsuarioDto::deArray(is_array($dados) ? $dados : []); $erros = $this->validator->validate($dto);
        if (count($erros) > 0) { $mensagens = []; foreach ($erros as $erro) $mensagens[$erro->getPropertyPath()] = $erro->getMessage(); throw new BadRequestHttpException(json_encode(['erros' => $mensagens], JSON_UNESCAPED_UNICODE)); }
        return $dto;
    }
}
