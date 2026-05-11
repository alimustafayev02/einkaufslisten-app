<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Erstellt die Tabellen für die Einkaufslisten-App.
 */
final class Version20250101000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial schema: shopping_list und shopping_list_item';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE shopping_list (
            id INT AUTO_INCREMENT NOT NULL,
            name VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE shopping_list_item (
            id INT AUTO_INCREMENT NOT NULL,
            shopping_list_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            quantity INT DEFAULT 1 NOT NULL,
            checked TINYINT(1) DEFAULT 0 NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_SHOPPING_LIST_ITEM_LIST (shopping_list_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE shopping_list_item
            ADD CONSTRAINT FK_SHOPPING_LIST_ITEM_LIST
            FOREIGN KEY (shopping_list_id) REFERENCES shopping_list (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE shopping_list_item DROP FOREIGN KEY FK_SHOPPING_LIST_ITEM_LIST');
        $this->addSql('DROP TABLE shopping_list_item');
        $this->addSql('DROP TABLE shopping_list');
    }
}
