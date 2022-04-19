<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEmailLog extends Migration
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
            'senders_id' => [
                'type' => 'BIGINT',
                'null' => true,
                'constraint' => 255,
            ],
            'recipients_email' => [
                'type' => 'VARCHAR',
                'null' => false,
                'constraint' => 128,
            ],
            'package_id' => [
                'type' => 'BIGINT',
                'null' => true,
                'constraint' => 255,
            ],
            'type' => [
                'type' => 'ENUM',
                'constraint' => array('in-locker', 'removed', 'locked', 'open-cell', 'locked-cell', 'canceled', 'other'),
                'default' => 'other'
            ],
            'auto' => [
                'type' => 'TINYINT',
                'null' => false,
                'default' => 0,
                'constraint' => 1,
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
        $this->forge->addKey('senders_id');
        $this->forge->addKey('recipients_email');
        $this->forge->addKey('package_id');
        $this->forge->addKey('type');
        $this->forge->addKey('auto');
        $this->forge->createTable('emaillogs');
    }

    public function down()
    {
        $this->forge->dropTable('emaillogs');
    }
}
