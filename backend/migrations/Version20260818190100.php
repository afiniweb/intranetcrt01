<?php

declare(strict_types=1);
namespace DoctrineMigrations;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260818190100 extends AbstractMigration
{
    public function getDescription(): string { return 'Normaliza datas futuras remanescentes após remoção do agendamento'; }
    public function up(Schema $schema): void { $this->addSql("UPDATE publicacoes SET publicar_em = CURRENT_TIMESTAMP, atualizado_em = CURRENT_TIMESTAMP WHERE status = 'PUBLICADA' AND publicar_em > CURRENT_TIMESTAMP"); }
    public function down(Schema $schema): void { $this->throwIrreversibleMigrationException('As datas de agendamento anteriores não podem ser reconstruídas.'); }
}
