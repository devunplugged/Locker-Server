<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTokenIssues extends Migration
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
                'null' => true,
                'constraint' => 255,
            ],
            'old_token_id' => [
                'type' => 'BIGINT',
                'null' => false,
                'constraint' => 255,
            ],
            'new_token_id' => [
                'type' => 'BIGINT',
                'null' => false,
                'constraint' => 255,
            ],
            'new_token' => [
                'type' => 'VARCHAR',
                'null' => false,
                'constraint' => 1024,
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => array('new','failed','done'),
                'null' => false,
                'default' => 'new',
            ],
            'old_token_uses' => [
                'type' => 'INT',
                'null' => false,
                'constraint' => 1,
                'default' => 0,
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
        $this->forge->addKey('old_token_id');
        $this->forge->addKey('new_token_id');
        $this->forge->createTable('tokenissues');
    }

    public function down()
    {
        $this->forge->dropTable('tokenissues');
    }
}
