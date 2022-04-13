<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRefCodeToPackages extends Migration
{
    public function up()
    {
        $fields = [
            'ref_code' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'after' => 'size',
            ],
        ];
        $this->forge->addColumn('packages', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('packages', 'ref_code');
    }
}
