<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMonitoringTables extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'report_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'scheduled_at' => ['type' => 'DATETIME'],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'pending'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['report_id', 'scheduled_at']);
        $this->forge->addForeignKey('report_id', 'shift_reports', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('monitoring_slots');

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'monitoring_slot_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'area_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'condition' => ['type' => 'VARCHAR', 'constraint' => 20],
            'finding_note' => ['type' => 'TEXT', 'null' => true],
            'action_note' => ['type' => 'TEXT', 'null' => true],
            'actual_observed_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['monitoring_slot_id', 'area_id']);
        $this->forge->addForeignKey('monitoring_slot_id', 'monitoring_slots', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('area_id', 'areas', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('monitoring_logs');
    }
    public function down() { $this->forge->dropTable('monitoring_logs', true); $this->forge->dropTable('monitoring_slots', true); }
}
