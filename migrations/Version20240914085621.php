<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240914085621 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE sub_advantage (id INT AUTO_INCREMENT NOT NULL, description VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sub_advantage_plan (sub_advantage_id INT NOT NULL, plan_id INT NOT NULL, INDEX IDX_1BFFAE6E2F360EBC (sub_advantage_id), INDEX IDX_1BFFAE6EE899029B (plan_id), PRIMARY KEY(sub_advantage_id, plan_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE sub_advantage_plan ADD CONSTRAINT FK_1BFFAE6E2F360EBC FOREIGN KEY (sub_advantage_id) REFERENCES sub_advantage (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sub_advantage_plan ADD CONSTRAINT FK_1BFFAE6EE899029B FOREIGN KEY (plan_id) REFERENCES plan (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sub_advantages_plan DROP FOREIGN KEY FK_74690A002589748C');
        $this->addSql('ALTER TABLE sub_advantages_plan DROP FOREIGN KEY FK_74690A00E899029B');
        $this->addSql('DROP TABLE sub_advantages');
        $this->addSql('DROP TABLE sub_advantages_plan');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE sub_advantages (id INT AUTO_INCREMENT NOT NULL, description VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE sub_advantages_plan (sub_advantages_id INT NOT NULL, plan_id INT NOT NULL, INDEX IDX_74690A002589748C (sub_advantages_id), INDEX IDX_74690A00E899029B (plan_id), PRIMARY KEY(sub_advantages_id, plan_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE sub_advantages_plan ADD CONSTRAINT FK_74690A002589748C FOREIGN KEY (sub_advantages_id) REFERENCES sub_advantages (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sub_advantages_plan ADD CONSTRAINT FK_74690A00E899029B FOREIGN KEY (plan_id) REFERENCES plan (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sub_advantage_plan DROP FOREIGN KEY FK_1BFFAE6E2F360EBC');
        $this->addSql('ALTER TABLE sub_advantage_plan DROP FOREIGN KEY FK_1BFFAE6EE899029B');
        $this->addSql('DROP TABLE sub_advantage');
        $this->addSql('DROP TABLE sub_advantage_plan');
    }
}
