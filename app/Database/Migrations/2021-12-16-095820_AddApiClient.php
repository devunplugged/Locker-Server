<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddApiClient extends Migration
{
    public function up()
    {
        helper('clients');

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 255,
                'unsigned' => true,
                'auto_increment' => true
            ],
            'company_id' => [
                'type' => 'BIGINT',
                'null' => true,
                'constraint' => 255,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'unique' => true,
                'null' => false,
                'constraint' => '255',
            ],
            'type' => [
                'type' => 'ENUM',
                'constraint' => getAllowedClientTypes(),
                'default' => 'staff'
            ],
            'active' => [
                'type' => 'INT',
                'null' => false,
                'constraint' => '1',
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
        $this->forge->createTable('apiclients');
    }

    public function down()
    {
        $this->forge->dropTable('apiclients');
    }
}
