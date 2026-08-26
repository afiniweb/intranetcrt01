<?php

namespace App\Security;

use App\Entity\Usuario;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class UsuarioAtivoChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void { if ($user instanceof Usuario && !$user->isAtivo()) throw new CustomUserMessageAccountStatusException('Usuário inativo.'); }
    public function checkPostAuth(UserInterface $user): void {}
}
