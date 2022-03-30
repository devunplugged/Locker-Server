<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class PackageAddTrackCodeDeleteUnused extends Migration
{
    public function up()
    {
        $fields = [
            'track_code' => [
                'type' => 'VARCHAR',
                'constraint' => '64',
                'null' => true,
                'after' => 'size',
            ],
        ];
        $this->forge->addColumn('packages', $fields);

        $this->forge->dropColumn('packages', 'recipient_email');
        $this->forge->dropColumn('packages', 'recipient_phone');
    }

    public function down()
    {
        $fields = [
            'recipient_email' => [
                'type' => 'VARCHAR',
                'constraint' => '64',
                'null' => true,
                'after' => 'size',
            ],
            'recipient_phone' => [
                'type' => 'VARCHAR',
                'constraint' => '13',
                'null' => true,
                'before' => 'recipient_code',
            ],
        ];
        $this->forge->addColumn('packages', $fields);
    }
}
