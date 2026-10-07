<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateShiftReportsTables extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'report_number' => ['type' => 'VARCHAR', 'constraint' => 60],
            'user_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'store_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'shift_template_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'supervisor_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'checklist_template_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'business_date' => ['type' => 'DATE'],
            'planned_start_at' => ['type' => 'DATETIME'],
            'planned_end_at' => ['type' => 'DATETIME'],
            'actual_start_at' => ['type' => 'DATETIME'],
            'ended_at' => ['type' => 'DATETIME', 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'draft'],
            'opening_note' => ['type' => 'TEXT', 'null' => true],
            'closing_note' => ['type' => 'TEXT', 'null' => true],
            'no_incident_confirmed' => ['type' => 'TINYINT', 'constraint' => 1, 'unsigned' => true, 'default' => 0],
            'row_version' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('report_number');
        $this->forge->addKey(['store_id', 'business_date', 'status']);
        $this->forge->addKey(['user_id', 'status']);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('store_id', 'stores', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('shift_template_id', 'shift_templates', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('supervisor_id', 'users', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('checklist_template_id', 'checklist_templates', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('shift_reports');

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'report_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'source_template_item_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'phase' => ['type' => 'VARCHAR', 'constraint' => 10],
            'item_text' => ['type' => 'VARCHAR', 'constraint' => 255],
            'sort_order' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'is_required' => ['type' => 'TINYINT', 'constraint' => 1, 'unsigned' => true],
            'allows_na' => ['type' => 'TINYINT', 'constraint' => 1, 'unsigned' => true, 'default' => 0],
            'answer' => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            'note' => ['type' => 'TEXT', 'null' => true],
            'answered_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['report_id', 'phase', 'sort_order']);
        $this->forge->addForeignKey('report_id', 'shift_reports', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('source_template_item_id', 'checklist_template_items', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('report_checklist_items');
    }

    public function down()
    {
        $this->forge->dropTable('report_checklist_items', true);
        $this->forge->dropTable('shift_reports', true);
    }
}
