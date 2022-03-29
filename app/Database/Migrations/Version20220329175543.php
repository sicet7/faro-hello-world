<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20220329175543 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function up(Schema $schema): void
    {
        $table = $schema->createTable('tests');
        $table->addColumn('id', Types::INTEGER)
            ->setNotnull(true)
            ->setAutoincrement(true);
        $table->setPrimaryKey(['id']);
        $table->addColumn('name', Types::STRING)->setNotnull(true)->setLength(255);
    }

    /**
     * @param Schema $schema
     * @return void
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function down(Schema $schema): void
    {
        if ($schema->hasTable('tests')) {
            $schema->dropTable('tests');
        }
    }
}
