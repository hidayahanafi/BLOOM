<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250303170225 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user DROP specializations, DROP languages_spoken, DROP therapeutic_approaches, DROP appointment_methods');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user ADD specializations JSON NOT NULL COMMENT \'(DC2Type:json)\', ADD languages_spoken JSON NOT NULL COMMENT \'(DC2Type:json)\', ADD therapeutic_approaches JSON NOT NULL COMMENT \'(DC2Type:json)\', ADD appointment_methods JSON NOT NULL COMMENT \'(DC2Type:json)\'');
    }
}
