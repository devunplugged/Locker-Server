<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddHeartbeat extends Migration
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
                'unique' => true,
                'constraint' => 255,
            ],
            'last_call_at' => [
                'type' => 'TIMESTAMP',
                'null' => true
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
        $this->forge->createTable('heartbeats');
    }

    public function down()
    {
        $this->forge->dropTable('heartbeats');
    }
}
