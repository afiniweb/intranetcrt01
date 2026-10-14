<?php

namespace App\Command;

use App\Entity\Escritorio;
use App\Entity\Instituicao;
use App\Entity\Usuario;
use App\Repository\EscritorioRepository;
use App\Repository\InstituicaoRepository;
use App\Repository\UsuarioRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:criar-admin-inicial', description: 'Cria a instituição, o escritório e o primeiro administrador global.')]
final class CriarAdminInicialCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly InstituicaoRepository $instituicoes,
        private readonly EscritorioRepository $escritorios,
        private readonly UsuarioRepository $usuarios,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        if ($this->usuarios->findOneBy(['perfil' => Usuario::PERFIL_ADMIN]) instanceof Usuario) {
            $io->note('Já existe um administrador. Nenhuma alteração foi realizada.');
            return Command::SUCCESS;
        }
        if (!$input->isInteractive()) {
            $io->error('Execute este comando em modo interativo para que a senha não fique exposta.');
            return Command::FAILURE;
        }

        $nomeInstituicao = trim((string) $io->ask('Nome da instituição', 'Conselho Regional dos Técnicos Industriais da 1ª Região', $this->textoObrigatorio(...)));
        $sigla = mb_strtoupper(trim((string) $io->ask('Sigla', 'CRT-01', $this->textoObrigatorio(...))));
        $nomeEscritorio = trim((string) $io->ask('Nome do escritório', 'Escritório Regional - Sede', $this->textoObrigatorio(...)));
        $uf = mb_strtoupper(trim((string) $io->ask('UF', 'DF', static function (?string $valor): string {
            $valor = trim((string) $valor);
            if (!preg_match('/^[A-Za-z]{2}$/', $valor)) throw new \RuntimeException('Informe uma UF com duas letras.');
            return $valor;
        })));
        $cidade = trim((string) $io->ask('Cidade', 'Brasília', $this->textoObrigatorio(...)));
        $nomeAdmin = trim((string) $io->ask('Nome do administrador', 'Administrador do sistema', $this->textoObrigatorio(...)));
        $email = mb_strtolower(trim((string) $io->ask('E-mail do administrador', 'admin@crt01.local', static function (?string $valor): string {
            $valor = trim((string) $valor);
            if (filter_var($valor, FILTER_VALIDATE_EMAIL) === false) throw new \RuntimeException('Informe um e-mail válido.');
            return $valor;
        })));
        if ($this->usuarios->findOneBy(['email' => $email]) instanceof Usuario) {
            $io->error('Já existe um usuário com esse e-mail.');
            return Command::FAILURE;
        }

        $senha = $this->perguntarSenha($input, $output);
        $instituicao = $this->instituicoes->findOneBy(['sigla' => $sigla]);
        $instituicaoNova = !$instituicao instanceof Instituicao;
        if ($instituicaoNova) {
            $instituicao = new Instituicao($nomeInstituicao, $sigla, null);
            $this->entityManager->persist($instituicao);
        }

        $escritorio = $instituicaoNova ? null : $this->escritorios->findOneBy(['instituicao' => $instituicao, 'nome' => $nomeEscritorio]);
        if (!$escritorio instanceof Escritorio) {
            $escritorio = new Escritorio($instituicao, $nomeEscritorio, $uf, $cidade, null);
            $this->entityManager->persist($escritorio);
        }

        $admin = new Usuario($instituicao, $escritorio, $nomeAdmin, $email, '', Usuario::PERFIL_ADMIN, true);
        $admin->alterarSenha($this->passwordHasher->hashPassword($admin, $senha));
        $this->entityManager->persist($admin);
        $this->entityManager->flush();

        $io->success(sprintf('Administrador global %s criado com sucesso.', $email));
        return Command::SUCCESS;
    }

    private function textoObrigatorio(?string $valor): string
    {
        $valor = trim((string) $valor);
        if ($valor === '') throw new \RuntimeException('Este valor é obrigatório.');
        return $valor;
    }

    private function perguntarSenha(InputInterface $input, OutputInterface $output): string
    {
        $helper = $this->getHelper('question');
        $pergunta = (new Question('Senha do administrador (mínimo de 12 caracteres): '))->setHidden(true)->setHiddenFallback(false);
        $pergunta->setValidator(static function (?string $senha): string {
            $senha = (string) $senha;
            if (mb_strlen($senha) < 12 || mb_strlen($senha) > 72) throw new \RuntimeException('A senha deve ter entre 12 e 72 caracteres.');
            return $senha;
        });
        $senha = (string) $helper->ask($input, $output, $pergunta);
        $confirmacao = (new Question('Confirme a senha: '))->setHidden(true)->setHiddenFallback(false);
        if ($senha !== (string) $helper->ask($input, $output, $confirmacao)) throw new \RuntimeException('As senhas não coincidem.');
        return $senha;
    }
}
