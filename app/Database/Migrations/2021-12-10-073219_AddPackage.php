<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPackage extends Migration
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
            'code' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
            ],
            'size' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => false,
            ],
            'recipient_email' => [
                'type' => 'VARCHAR',
                'constraint' => '64',
                'null' => true,
            ],
            'recipient_phone' => [
                'type' => 'VARCHAR',
                'constraint' => '13',
                'null' => true,
            ],
            'recipient_code' => [
                'type' => 'VARCHAR',
                'constraint' => '13',
                'null' => true,
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => array('new', 'insert-ready', 'in-locker', 'remove-ready', 'removed', 'locked'),
                'default' => 'new'
            ],
            'locker_id' => [
                'type' => 'BIGINT',
                'null' => false,
                'constraint' => 255,
            ],
            'cell_sort_id' => [
                'type' => 'BIGINT',
                'constraint' => 255,
                'unsigned' => true,
                'null' => true,
            ],
            'company_id' => [
                'type' => 'BIGINT',
                'null' => true,
                'constraint' => 255,
            ],
            'created_by' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => true,
            ],
            'inserted_by' => [
                'type' => 'BIGINT',
                'constraint' => 255,
                'unsigned' => true,
                'null' => true,
            ],
            'insert_method' => [
                'type' => 'VARCHAR',
                'constraint' => '64',
                'null' => true,
            ],
            'remove_method' => [
                'type' => 'VARCHAR',
                'constraint' => '64',
                'null' => true,
            ],
            'insert_cancelled_by' => [
                'type' => 'VARCHAR',
                'constraint' => '64',
                'null' => true,
            ],
            'enter_code_entered_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'recipient_code_entered_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'insert_cancelled_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'inserted_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'removed_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('locker_id');
        $this->forge->addKey('cell_sort_id');
        $this->forge->addKey('company_id');
        $this->forge->createTable('packages');
    }

    public function down()
    {
        $this->forge->dropTable('packages');
    }
}
