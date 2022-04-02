<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCanceledAtToPackages extends Migration
{
    public function up()
    {
        $fields = [
            'canceled_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
        ];
        $this->forge->addColumn('packages', $fields);

        $this->forge->dropColumn('packages', 'inserted_by');
        $this->forge->dropColumn('packages', 'insert_method');
        $this->forge->dropColumn('packages', 'remove_method');
        $this->forge->dropColumn('packages', 'insert_cancelled_by');
        $this->forge->dropColumn('packages', 'insert_cancelled_at');

        $fields = [
            'status' => [
                'type' => 'ENUM',
                'constraint' => array('new', 'insert-ready', 'in-locker', 'remove-ready', 'removed', 'locked', 'canceled'),
                'default' => 'new'
            ],
        ];
        $this->forge->modifyColumn('packages', $fields);
    }

    public function down()
    {
        $fields = [
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
        ];
        $this->forge->addColumn('packages', $fields);

        $fields = [
            'status' => [
                'type' => 'ENUM',
                'constraint' => array('new', 'insert-ready', 'in-locker', 'remove-ready', 'removed', 'locked'),
                'default' => 'new'
            ],
        ];
        $this->forge->modifyColumn('packages', $fields);
    }
}
