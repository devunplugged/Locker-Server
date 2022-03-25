<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCell extends Migration
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
            'cell_sort_id' => [
                'type' => 'BIGINT',
                'null' => true,
                'constraint' => 255,
            ],
            'locker_id' => [
                'type' => 'BIGINT',
                'null' => false,
                'constraint' => 255,
            ],
            'size' => [
                'type' => 'VARCHAR',
                'constraint' => '1',
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => array('open', 'closed', 'out-of-order'),
                'default' => 'closed'
            ],
            'service' => [
                'type' => 'INT',
                'constraint' => '1',
                'default' => 0
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
        $this->forge->createTable('cells');
    }

    public function down()
    {
        $this->forge->dropTable('cells');
    }
}
