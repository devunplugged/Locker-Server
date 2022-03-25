<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFailedOpenAttempts extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 255,
                'unsigned' => true,
                'auto_increment' => true
            ],
            'client_id' => [
                'type' => 'BIGINT',
                'null' => false,
                'constraint' => 255,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => true
            ],
            'updated_at' => [
                'type' => 'TIMESTAMP',
                'null' => true
            ],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('client_id');
        $this->forge->createTable('failedopenattempts');
    }

    public function down()
    {
        $this->forge->dropTable('failedopenattempts');
    }
}
