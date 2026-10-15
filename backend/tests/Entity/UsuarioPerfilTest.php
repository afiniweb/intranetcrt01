<?php

namespace App\Tests\Entity;

use App\Entity\Escritorio;
use App\Entity\Instituicao;
use App\Entity\Usuario;
use App\Dto\Usuario\SalvarUsuarioDto;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class UsuarioPerfilTest extends TestCase
{
    public function testPerfisConcedemSomenteAsRolesPrevistas(): void
    {
        $instituicao = new Instituicao('Instituição', 'TESTE', null);
        $escritorio = new Escritorio($instituicao, 'Sede', 'DF', 'Brasília', null);
        foreach ([
            'LEITOR' => ['ROLE_USER'],
            'PUBLICADOR' => ['ROLE_PUBLICADOR', 'ROLE_USER'],
            'ADMIN' => ['ROLE_ADMIN', 'ROLE_USER'],
            'INVALIDO' => [],
        ] as $perfil => $roles) {
            $usuario = new Usuario($instituicao, $escritorio, 'Teste', 'teste@example.com', '', $perfil, true);
            self::assertSame($roles, $usuario->getRoles());
            self::assertSame($perfil === 'ADMIN', $usuario->isAdminGlobal());
        }
    }

    public function testAlteracaoParaLeitorRemovePrivilegios(): void
    {
        $instituicao = new Instituicao('Instituição', 'TESTE', null);
        $escritorio = new Escritorio($instituicao, 'Sede', 'DF', 'Brasília', null);
        $usuario = new Usuario($instituicao, $escritorio, 'Teste', 'teste@example.com', '', 'ADMIN', true);
        $usuario->atualizar($instituicao, $escritorio, 'Teste', 'teste@example.com', 'LEITOR', true, true);
        self::assertSame(['ROLE_USER'], $usuario->getRoles());
        self::assertFalse($usuario->isAdminGlobal());
    }

    public function testCadastroAceitaLeitorERejeitaPerfilDesconhecido(): void
    {
        $validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
        foreach (['LEITOR' => 0, 'INVALIDO' => 1] as $perfil => $erros) {
            $dto = new SalvarUsuarioDto(1, 1, 'Teste', 'teste@example.com', $perfil, false, 'senha-teste');
            self::assertCount($erros, $validator->validate($dto));
        }
    }
}
