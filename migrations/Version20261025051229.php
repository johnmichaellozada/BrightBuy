<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261025051229 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add an optional category relationship to products';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE product ADD category_id INT DEFAULT NULL'
        );

        $this->addSql(
            'ALTER TABLE product ADD CONSTRAINT FK_D34A04AD12469DE2
             FOREIGN KEY (category_id) REFERENCES category (id)
             ON DELETE SET NULL'
        );

        $this->addSql(
            'CREATE INDEX IDX_D34A04AD12469DE2 ON product (category_id)'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD12469DE2'
        );

        $this->addSql(
            'DROP INDEX IDX_D34A04AD12469DE2 ON product'
        );

        $this->addSql(
            'ALTER TABLE product DROP category_id'
        );
    }
}
