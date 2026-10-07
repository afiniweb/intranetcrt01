<?php

namespace App\Command;

use App\Entity\Publicacao;
use App\Entity\TipoConteudo;
use App\Entity\Usuario;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsCommand(name: 'app:carregar-demonstracao', description: 'Adiciona conteúdos fictícios para testar o layout em desenvolvimento.')]
final class CarregarDemonstracaoCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $em, private readonly KernelInterface $kernel)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('autor', InputArgument::REQUIRED, 'ID do usuário ativo responsável pelos conteúdos.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        if (!in_array($this->kernel->getEnvironment(), ['dev', 'test'], true)) {
            $io->error('A carga de demonstração está disponível somente em dev ou test.');
            return Command::FAILURE;
        }
        $id = filter_var($input->getArgument('autor'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $autor = $id === false ? null : $this->em->find(Usuario::class, $id);
        if (!$autor instanceof Usuario || !$autor->isAtivo() || !$autor->getInstituicao()->isAtivo() || !$autor->getEscritorio()->isAtivo()) {
            $io->error('Informe um usuário ativo, vinculado a uma instituição e escritório ativos.');
            return Command::INVALID;
        }

        $catalogo = require $this->kernel->getProjectDir().'/resources/conteudos-demonstracao.php';
        $criadas = 0;
        $ignoradas = 0;
        $this->em->wrapInTransaction(function () use ($catalogo, $autor, &$criadas, &$ignoradas): void {
            foreach ($catalogo as $nome => $dados) {
                $nome = '[Demo] '.$nome;
                $tipo = $this->em->getRepository(TipoConteudo::class)->findOneBy(['instituicao' => $autor->getInstituicao(), 'nome' => $nome]);
                $novo = $tipo === null;
                if ($novo) {
                    $tipo = new TipoConteudo($autor->getInstituicao(), $autor, $nome, $dados['descricao']);
                    $this->em->persist($tipo);
                }
                foreach ($dados['itens'] as $item) {
                    $titulo = '[Demo] '.$item[0];
                    if (!$novo && $this->em->getRepository(Publicacao::class)->findOneBy(['tipoConteudo' => $tipo, 'titulo' => $titulo]) !== null) {
                        ++$ignoradas;
                        continue;
                    }
                    $publicacao = new Publicacao($tipo, $autor, $titulo, "Conteúdo fictício para demonstração.\n\n".$item[1], $item[2] ?? null);
                    $this->em->persist($publicacao);
                    ++$criadas;
                }
            }
        });
        $io->success(sprintf('%d publicações criadas; %d já existentes. Instituição: %s.', $criadas, $ignoradas, $autor->getInstituicao()->getNome()));
        return Command::SUCCESS;
    }
}
