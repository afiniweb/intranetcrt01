<?php

namespace App\Controller\Api\V1;

use App\Entity\Usuario;
use App\Service\Mensagem\MensagemService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/mensagens')]
#[IsGranted('ROLE_USER')]
final class MensagemController extends AbstractController
{
    public function __construct(private readonly MensagemService $service) {}
    private function usuario(): Usuario
    {
        $u = $this->getUser();
        if (!$u instanceof Usuario) throw $this->createAccessDeniedException();
        return $u;
    }
    #[Route('', methods: ['GET'])]
    public function listar(Request $r): JsonResponse { return $this->json($this->service->listar($this->usuario(), $r->query->getInt('pagina', 1), (string) $r->query->get('busca', ''))); }
    #[Route('', methods: ['POST'])]
    public function criar(Request $r): JsonResponse { return $this->json($this->service->salvar(null, $this->usuario(), $r->toArray()), 201); }
    #[Route('/{id<\d+>}', methods: ['PUT'])]
    public function editar(int $id, Request $r): JsonResponse { return $this->json($this->service->salvar($id, $this->usuario(), $r->toArray())); }
    #[Route('/{id<\d+>}', methods: ['DELETE'])]
    public function excluir(int $id): Response { $this->service->excluir($id, $this->usuario()); return new Response(status: 204); }
    #[Route('/{id<\d+>}/ciencias', methods: ['GET'])]
    public function ciencias(int $id, Request $r): JsonResponse { return $this->json($this->service->ciencias($id, $this->usuario(), $r->query->getInt('pagina', 1))); }
    #[Route('/pendente', methods: ['GET'])]
    public function pendente(Request $r): JsonResponse { return $this->json(['mensagem' => $this->service->pendente($this->usuario(), $r->getSession())?->paraArray()], 200, ['Cache-Control' => 'no-store']); }
    #[Route('/{id<\d+>}/apresentar', methods: ['POST'])]
    public function apresentar(int $id, Request $r): JsonResponse { return $this->json($this->service->apresentar($id, $this->usuario(), $r->getSession()), 200, ['Cache-Control' => 'no-store']); }
    #[Route('/{id<\d+>}/responder', methods: ['POST'])]
    public function responder(int $id, Request $r): Response
    {
        $dados = $r->toArray();
        if (!is_bool($dados['aceitar'] ?? null) || !is_int($dados['versao'] ?? null)) throw new BadRequestHttpException('Informe ciência ou dispensa e a versão apresentada.');
        $this->service->responder($id, $this->usuario(), $r->getSession(), $dados['aceitar'], $dados['versao']);
        return new Response(status: 204);
    }
}
