<?php

declare(strict_types=1);
namespace DoctrineMigrations;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260818190000 extends AbstractMigration
{
    public function getDescription(): string { return 'Publica automaticamente conteúdos ainda submetidos ao fluxo editorial antigo'; }
    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE publicacoes SET status = 'PUBLICADA', publicar_em = CURRENT_TIMESTAMP, justificativa_rejeicao = NULL, atualizado_em = CURRENT_TIMESTAMP WHERE status IN ('RASCUNHO', 'AGUARDANDO_APROVACAO', 'APROVADA_AGENDADA', 'REJEITADA')");
    }
    public function down(Schema $schema): void { $this->throwIrreversibleMigrationException('Não é possível reconstruir com segurança os estados editoriais anteriores.'); }
}
