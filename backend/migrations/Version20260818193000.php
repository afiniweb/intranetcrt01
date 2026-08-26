<?php

declare(strict_types=1);
namespace DoctrineMigrations;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260818193000 extends AbstractMigration
{
    public function getDescription(): string { return 'Adiciona arquivo PDF opcional às publicações'; }
    public function up(Schema $schema): void { $this->addSql('ALTER TABLE publicacoes ADD arquivo_pdf VARCHAR(255) DEFAULT NULL'); }
    public function down(Schema $schema): void { $this->addSql('ALTER TABLE publicacoes DROP arquivo_pdf'); }
}
