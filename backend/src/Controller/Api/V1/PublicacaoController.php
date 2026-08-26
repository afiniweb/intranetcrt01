<?php

namespace App\Controller\Api\V1;

use App\Dto\Publicacao\SalvarPublicacaoDto;
use App\Entity\Usuario;
use App\Service\Publicacao\PublicacaoService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1/publicacoes')] #[IsGranted('ROLE_USER')] #[OA\Tag(name: 'Publicações')]
final class PublicacaoController extends AbstractController
{
    public function __construct(private readonly PublicacaoService $service, private readonly ValidatorInterface $validator) {}
    #[Route('', methods: ['GET'])] public function listar(Request $request): JsonResponse { return new JsonResponse($this->service->listar($this->usuario(), (string) $request->query->get('busca', ''), max(1, $request->query->getInt('pagina', 1)), min(100, max(1, $request->query->getInt('porPagina', 10))))); }
    #[Route('', methods: ['POST'])] public function criar(Request $request): JsonResponse { $arquivo = $request->files->get('arquivo'); if ($arquivo !== null && !$arquivo instanceof UploadedFile) throw new BadRequestHttpException('Arquivo inválido.'); return new JsonResponse($this->service->criar($this->usuario(), $this->dto($request), $arquivo)->paraArray(), Response::HTTP_CREATED); }
    #[Route('/{id<\d+>}', methods: ['DELETE'])] public function arquivar(int $id): JsonResponse { return new JsonResponse($this->service->arquivar($id, $this->usuario())->paraArray()); }
    #[Route('/{id<\d+>}/reativar', methods: ['POST'])] public function reativar(int $id): JsonResponse { return new JsonResponse($this->service->reativar($id, $this->usuario())->paraArray()); }
    #[Route('/{id<\d+>}/arquivo', methods: ['GET'])] public function arquivo(int $id): BinaryFileResponse { $resposta = new BinaryFileResponse($this->service->caminhoArquivo($id, $this->usuario())); $resposta->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, 'publicacao-'.$id.'.pdf'); $resposta->setPrivate(); $resposta->setMaxAge(0); $resposta->headers->set('Cache-Control', 'private, no-store, max-age=0'); $resposta->headers->set('X-Content-Type-Options', 'nosniff'); return $resposta; }
    private function usuario(): Usuario { $usuario = $this->getUser(); if (!$usuario instanceof Usuario) throw $this->createAccessDeniedException(); return $usuario; }
    private function dto(Request $request): SalvarPublicacaoDto { if (str_starts_with((string) $request->headers->get('Content-Type'), 'multipart/form-data')) $dados = $request->request->all(); else { try { $dados = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR); } catch (\JsonException) { throw new BadRequestHttpException('JSON inválido.'); } } $dto = SalvarPublicacaoDto::deArray(is_array($dados) ? $dados : []); if (count($this->validator->validate($dto)) > 0) throw new BadRequestHttpException('Título, corpo e tipo de conteúdo são obrigatórios.'); return $dto; }
}
