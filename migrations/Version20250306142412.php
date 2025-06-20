<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250306142412 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE objectif ADD user_id INT DEFAULT NULL, DROP id_user');
        $this->addSql('ALTER TABLE objectif ADD CONSTRAINT FK_E2F86851A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_E2F86851A76ED395 ON objectif (user_id)');
        $this->addSql('ALTER TABLE recompense ADD user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE recompense ADD CONSTRAINT FK_1E9BC0DEA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_1E9BC0DEA76ED395 ON recompense (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE objectif DROP FOREIGN KEY FK_E2F86851A76ED395');
        $this->addSql('DROP INDEX IDX_E2F86851A76ED395 ON objectif');
        $this->addSql('ALTER TABLE objectif ADD id_user VARCHAR(255) DEFAULT NULL, DROP user_id');
        $this->addSql('ALTER TABLE recompense DROP FOREIGN KEY FK_1E9BC0DEA76ED395');
        $this->addSql('DROP INDEX IDX_1E9BC0DEA76ED395 ON recompense');
        $this->addSql('ALTER TABLE recompense DROP user_id');
    }
}
