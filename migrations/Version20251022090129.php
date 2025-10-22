<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251022090129 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE groupe ADD proprietaire_id INT NOT NULL');
        $this->addSql('ALTER TABLE groupe ADD CONSTRAINT FK_4B98C2176C50E4A FOREIGN KEY (proprietaire_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_4B98C2176C50E4A ON groupe (proprietaire_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE groupe DROP FOREIGN KEY FK_4B98C2176C50E4A');
        $this->addSql('DROP INDEX IDX_4B98C2176C50E4A ON groupe');
        $this->addSql('ALTER TABLE groupe DROP proprietaire_id');
    }
}
