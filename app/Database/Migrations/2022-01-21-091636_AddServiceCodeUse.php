<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddServiceCodeUse extends Migration
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
            'code_id' => [
                'type' => 'BIGINT',
                'null' => false,
                'constraint' => 255,
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => array('execute', 'executed', 'canceled'),
                'null' => false,
                'default' => 'execute',
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
        $this->forge->addKey('code_id');
        $this->forge->createTable('servicecodeuses');
    }

    public function down()
    {
        $this->forge->dropTable('servicecodeuses');
    }
}
