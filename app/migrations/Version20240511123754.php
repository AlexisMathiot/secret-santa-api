<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240511123754 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE invitation (id INT AUTO_INCREMENT NOT NULL, user_to_invit_id INT DEFAULT NULL, event_id INT DEFAULT NULL, user_sent_invit_id INT DEFAULT NULL, date DATETIME NOT NULL, INDEX IDX_F11D61A22149D473 (user_to_invit_id), INDEX IDX_F11D61A271F7E88B (event_id), INDEX IDX_F11D61A226F7AADA (user_sent_invit_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE invitation ADD CONSTRAINT FK_F11D61A22149D473 FOREIGN KEY (user_to_invit_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE invitation ADD CONSTRAINT FK_F11D61A271F7E88B FOREIGN KEY (event_id) REFERENCES event (id)');
        $this->addSql('ALTER TABLE invitation ADD CONSTRAINT FK_F11D61A226F7AADA FOREIGN KEY (user_sent_invit_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE invitation DROP FOREIGN KEY FK_F11D61A22149D473');
        $this->addSql('ALTER TABLE invitation DROP FOREIGN KEY FK_F11D61A271F7E88B');
        $this->addSql('ALTER TABLE invitation DROP FOREIGN KEY FK_F11D61A226F7AADA');
        $this->addSql('DROP TABLE invitation');
    }
}
