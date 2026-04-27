<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSsoColumnsToUser extends Migration
{
    public function up()
    {
        $fields = [
            'azure_oid' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => true,
            ],
            'email' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'auth_source' => [
                'type'       => 'VARCHAR',
                'constraint' => 16,
                'default'    => 'local',
            ],
            'last_login_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ];

        $this->forge->addColumn('user', $fields);
        $this->db->query('CREATE UNIQUE INDEX idx_user_azure_oid ON user (azure_oid)');
    }

    public function down()
    {
        $this->db->query('DROP INDEX idx_user_azure_oid ON user');
        $this->forge->dropColumn('user', ['azure_oid', 'email', 'name', 'auth_source', 'last_login_at']);
    }
}
