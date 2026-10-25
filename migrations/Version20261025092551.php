<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261025092551 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the order table for admin Order Management';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE `order` (id INT AUTO_INCREMENT NOT NULL, order_number VARCHAR(30) NOT NULL, customer_name VARCHAR(150) NOT NULL, customer_email VARCHAR(180) NOT NULL, customer_phone VARCHAR(30) DEFAULT NULL, shipping_address LONGTEXT NOT NULL, total_amount NUMERIC(10, 2) NOT NULL, status VARCHAR(30) NOT NULL, payment_status VARCHAR(30) NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_F5299398551F0F81 (order_number), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE `order`');
    }
}
