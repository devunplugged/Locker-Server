<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTask extends Migration
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
            
            'type' => [
                'type' => 'ENUM',
                'constraint' => array('open-cell','close-cell'),
                'null' => false,
                'default' => 'open-cell',
            ],
            'value' => [
                'type' => 'VARCHAR',
                'null' => false,
                'constraint' => 32,
            ],
            'attempts' => [
                'type' => 'INT',
                'null' => false,
                'constraint' => 2,
                'default' => 0,
            ],
            'done_at' => [
                'type' => 'TIMESTAMP',
                'null' => true
            ],
            'failed_at' => [
                'type' => 'TIMESTAMP',
                'null' => true
            ],
            'sent_at' => [
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
        $this->forge->addKey('locker_id');
        $this->forge->createTable('tasks');
    }

    public function down()
    {
        $this->forge->dropTable('tasks');
    }
}
