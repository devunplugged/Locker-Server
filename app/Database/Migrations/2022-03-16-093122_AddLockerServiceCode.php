<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLockerServiceCode extends Migration
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
            'code' => [
                'type' => 'VARCHAR',
                'unique' => true,
                'null' => false,
                'constraint' => 32,
            ],
            'action' => [
                'type' => 'ENUM',
                'constraint' => array('open-cell'),
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
        $this->forge->addKey('locker_id');
        $this->forge->createTable('lockerservicecodes');
    }

    public function down()
    {
        $this->forge->dropTable('lockerservicecodes');
    }
}
