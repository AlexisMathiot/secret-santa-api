<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240106154039 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE gift_list DROP FOREIGN KEY FK_B6B50A45A76ED395');
        $this->addSql('DROP INDEX UNIQ_B6B50A45A76ED395 ON gift_list');
        $this->addSql('ALTER TABLE gift_list DROP user_id');
        $this->addSql('ALTER TABLE user ADD gift_list_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D64951F42524 FOREIGN KEY (gift_list_id) REFERENCES gift_list (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D64951F42524 ON user (gift_list_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE gift_list ADD user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE gift_list ADD CONSTRAINT FK_B6B50A45A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B6B50A45A76ED395 ON gift_list (user_id)');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D64951F42524');
        $this->addSql('DROP INDEX UNIQ_8D93D64951F42524 ON user');
        $this->addSql('ALTER TABLE user DROP gift_list_id');
    }
}
