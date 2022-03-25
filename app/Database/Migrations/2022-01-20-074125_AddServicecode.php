<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddServicecode extends Migration
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
            'company_id' => [
                'type' => 'BIGINT',
                'null' => false,
                'constraint' => 255,
            ],
            'code' => [
                'type' => 'VARCHAR',
                'unique' => true,
                'null' => false,
                'constraint' => 32,
            ],
            'action' => [
                'type' => 'ENUM',
                'constraint' => array('reset-size-a', 'reset-size-b', 'open-cell'),
                'null' => true,
            ],
            'value' => [
                'type' => 'INT',
                'null' => true,
                'constraint' => 3,
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
        $this->forge->addKey('company_id');
        $this->forge->createTable('servicecodes');
    }

    public function down()
    {
        $this->forge->dropTable('servicecodes');
    }
}
