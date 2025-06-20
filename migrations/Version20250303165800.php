<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250303165800 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user CHANGE specializations specializations JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', CHANGE languages_spoken languages_spoken JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', CHANGE therapeutic_approaches therapeutic_approaches JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', CHANGE appointment_methods appointment_methods JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user CHANGE specializations specializations JSON NOT NULL COMMENT \'(DC2Type:json)\', CHANGE languages_spoken languages_spoken JSON NOT NULL COMMENT \'(DC2Type:json)\', CHANGE therapeutic_approaches therapeutic_approaches JSON NOT NULL COMMENT \'(DC2Type:json)\', CHANGE appointment_methods appointment_methods JSON NOT NULL COMMENT \'(DC2Type:json)\'');
    }
}
