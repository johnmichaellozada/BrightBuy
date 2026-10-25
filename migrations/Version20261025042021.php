<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261025042021 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the product table for BrightBuy';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE product (
                id INT AUTO_INCREMENT NOT NULL,
                name VARCHAR(255) NOT NULL,
                description LONGTEXT DEFAULT NULL,
                price NUMERIC(10, 2) NOT NULL,
                stock INT NOT NULL,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4
              COLLATE `utf8mb4_unicode_ci`
              ENGINE = InnoDB'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE product');
    }
}
