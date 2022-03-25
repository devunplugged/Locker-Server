<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPackageAddress extends Migration
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
            'package_id' => [
                'type' => 'BIGINT',
                'null' => false,
                'constraint' => 255,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => false,
            ],
            'value' => [
                'type' => 'VARCHAR',
                'null' => false,
                'constraint' => 256,
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
        $this->forge->addKey('package_id');
        $this->forge->createTable('packageaddresses');
    }

    public function down()
    {
        $this->forge->dropTable('packageaddresses');
    }
}
