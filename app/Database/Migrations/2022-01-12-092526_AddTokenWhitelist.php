<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTokenWhitelist extends Migration
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
            'description' => [
                'type' => 'VARCHAR',
                'null' => true,
                'constraint' => '256',
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
        $this->forge->createTable('tokenwhitelists');
    }

    public function down()
    {
        $this->forge->dropTable('tokenwhitelists');
    }
}
