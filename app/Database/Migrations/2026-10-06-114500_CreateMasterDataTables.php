<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMasterDataTables extends Migration
{
    public function up()
    {
        $this->createStores();
        $this->createAreas();
        $this->createCameras();
        $this->createShiftTemplates();
        $this->createIncidentTypes();
        $this->createChecklistTemplates();
        $this->createChecklistTemplateItems();
        $this->createUserStores();
    }

    public function down()
    {
        $this->forge->dropTable('user_stores', true);
        $this->forge->dropTable('checklist_template_items', true);
        $this->forge->dropTable('checklist_templates', true);
        $this->forge->dropTable('incident_types', true);
        $this->forge->dropTable('shift_templates', true);
        $this->forge->dropTable('cameras', true);
        $this->forge->dropTable('areas', true);
        $this->forge->dropTable('stores', true);
    }

    private function createStores(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'code' => ['type' => 'VARCHAR', 'constraint' => 30],
            'name' => ['type' => 'VARCHAR', 'constraint' => 150],
            'address' => ['type' => 'TEXT', 'null' => true],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'unsigned' => true, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code');
        $this->forge->createTable('stores');
    }

    private function createAreas(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'store_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 150],
            'priority' => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'medium'],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'unsigned' => true, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('store_id');
        $this->forge->addUniqueKey(['store_id', 'name']);
        $this->forge->addForeignKey('store_id', 'stores', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('areas');
    }

    private function createCameras(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'store_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'area_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'code' => ['type' => 'VARCHAR', 'constraint' => 30],
            'label' => ['type' => 'VARCHAR', 'constraint' => 150],
            'dvr_channel' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'unsigned' => true, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['store_id', 'area_id']);
        $this->forge->addUniqueKey(['store_id', 'code']);
        $this->forge->addForeignKey('store_id', 'stores', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('area_id', 'areas', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('cameras');
    }

    private function createShiftTemplates(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'store_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 100],
            'start_time' => ['type' => 'TIME'],
            'end_time' => ['type' => 'TIME'],
            'monitoring_interval_minutes' => ['type' => 'SMALLINT', 'constraint' => 4, 'unsigned' => true, 'default' => 120],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'unsigned' => true, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('store_id');
        $this->forge->addUniqueKey(['store_id', 'name']);
        $this->forge->addForeignKey('store_id', 'stores', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('shift_templates');
    }

    private function createIncidentTypes(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'code' => ['type' => 'VARCHAR', 'constraint' => 50],
            'name' => ['type' => 'VARCHAR', 'constraint' => 150],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'unsigned' => true, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code');
        $this->forge->addUniqueKey('name');
        $this->forge->createTable('incident_types');
    }

    private function createChecklistTemplates(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 150],
            'version' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'description' => ['type' => 'TEXT', 'null' => true],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'unsigned' => true, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['name', 'version']);
        $this->forge->createTable('checklist_templates');
    }

    private function createChecklistTemplateItems(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'checklist_template_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'phase' => ['type' => 'VARCHAR', 'constraint' => 10],
            'item_text' => ['type' => 'VARCHAR', 'constraint' => 255],
            'sort_order' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'is_required' => ['type' => 'TINYINT', 'constraint' => 1, 'unsigned' => true, 'default' => 1],
            'allows_na' => ['type' => 'TINYINT', 'constraint' => 1, 'unsigned' => true, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('checklist_template_id');
        $this->forge->addUniqueKey(['checklist_template_id', 'phase', 'sort_order']);
        $this->forge->addForeignKey('checklist_template_id', 'checklist_templates', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('checklist_template_items');
    }

    private function createUserStores(): void
    {
        $this->forge->addField([
            'user_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'store_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey(['user_id', 'store_id'], true);
        $this->forge->addKey('store_id');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('store_id', 'stores', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('user_stores');
    }
}
