<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDiagnostic extends Migration
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
            'locker_id' => [
                'type' => 'BIGINT',
                'null' => false,
                'constraint' => 255,
            ],
            'temperature' => [
                'type' => 'FLOAT',
                'constraint' => [3,1],
                'null' => true,
            ],
            'humidity' => [
                'type' => 'FLOAT',
                'constraint' => [3,1],
                'null' => true,
            ],
            'voltage' => [
                'type' => 'FLOAT',
                'constraint' => [3,1],
                'null' => true,
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
        $this->forge->addKey('locker_id');
        $this->forge->createTable('diagnostics');
    }

    public function down()
    {
        $this->forge->dropTable('diagnostics');
    }
}
