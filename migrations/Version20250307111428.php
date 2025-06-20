<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250307111428 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE panier (id INT AUTO_INCREMENT NOT NULL, id_user_id INT NOT NULL, quantite INT NOT NULL, medicament_quantities JSON NOT NULL COMMENT \'(DC2Type:json)\', date_ajout DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_24CC0DF279F37AE5 (id_user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE panier_medicament (panier_id INT NOT NULL, medicament_id INT NOT NULL, INDEX IDX_FE494BAFF77D927C (panier_id), INDEX IDX_FE494BAFAB0D61F7 (medicament_id), PRIMARY KEY(panier_id, medicament_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE panier ADD CONSTRAINT FK_24CC0DF279F37AE5 FOREIGN KEY (id_user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE panier_medicament ADD CONSTRAINT FK_FE494BAFF77D927C FOREIGN KEY (panier_id) REFERENCES panier (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE panier_medicament ADD CONSTRAINT FK_FE494BAFAB0D61F7 FOREIGN KEY (medicament_id) REFERENCES medicament (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE panier DROP FOREIGN KEY FK_24CC0DF279F37AE5');
        $this->addSql('ALTER TABLE panier_medicament DROP FOREIGN KEY FK_FE494BAFF77D927C');
        $this->addSql('ALTER TABLE panier_medicament DROP FOREIGN KEY FK_FE494BAFAB0D61F7');
        $this->addSql('DROP TABLE panier');
        $this->addSql('DROP TABLE panier_medicament');
    }
}
