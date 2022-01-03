<?php

namespace App\Console\Database\Migrations;

use Doctrine\DBAL\Types\Types;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class CreateTestTable extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
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
     */
    public function down(Schema $schema): void
    {
        if ($schema->hasTable('tests')) {
            $schema->dropTable('tests');
        }
    }
}
