<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250606090103 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE advice (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, description VARCHAR(255) NOT NULL, INDEX IDX_64820E8DA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE advice_month (advice_id INT NOT NULL, month_id INT NOT NULL, INDEX IDX_111E58B112998205 (advice_id), INDEX IDX_111E58B1A0CBDE4 (month_id), PRIMARY KEY(advice_id, month_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE month (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, city VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE advice ADD CONSTRAINT FK_64820E8DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE advice_month ADD CONSTRAINT FK_111E58B112998205 FOREIGN KEY (advice_id) REFERENCES advice (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE advice_month ADD CONSTRAINT FK_111E58B1A0CBDE4 FOREIGN KEY (month_id) REFERENCES month (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE advice DROP FOREIGN KEY FK_64820E8DA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE advice_month DROP FOREIGN KEY FK_111E58B112998205
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE advice_month DROP FOREIGN KEY FK_111E58B1A0CBDE4
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE advice
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE advice_month
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE month
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE user
        SQL);
    }
}
