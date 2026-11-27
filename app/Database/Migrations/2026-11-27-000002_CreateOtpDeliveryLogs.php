<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateOtpDeliveryLogs extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'phone' => ['type' => 'VARCHAR', 'constraint' => 24],
            'device_id' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 32],
            'message' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'ip_address' => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'created_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('phone');
        $this->forge->addKey('status');
        $this->forge->addKey('created_at');
        $this->forge->createTable('otp_delivery_logs', true);
    }

    public function down()
    {
        $this->forge->dropTable('otp_delivery_logs', true);
    }
}
