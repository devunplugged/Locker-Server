<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDetail extends Migration
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
            'name' => [
                'type' => 'VARCHAR',
                'null' => false,
                'constraint' => '255',
            ],
            'value' => [
                'type' => 'TEXT',
                'null' => false,
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
        $this->forge->createTable('details');
    }

    public function down()
    {
        $this->forge->dropTable('details');
    }
}
