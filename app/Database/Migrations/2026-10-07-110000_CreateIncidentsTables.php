<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;
class CreateIncidentsTables extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type'=>'BIGINT','constraint'=>20,'unsigned'=>true,'auto_increment'=>true],
            'report_id' => ['type'=>'BIGINT','constraint'=>20,'unsigned'=>true],
            'store_id' => ['type'=>'BIGINT','constraint'=>20,'unsigned'=>true],
            'area_id' => ['type'=>'BIGINT','constraint'=>20,'unsigned'=>true],
            'camera_id' => ['type'=>'BIGINT','constraint'=>20,'unsigned'=>true,'null'=>true],
            'incident_type_id' => ['type'=>'BIGINT','constraint'=>20,'unsigned'=>true],
            'occurred_at' => ['type'=>'DATETIME'],
            'description' => ['type'=>'TEXT'],
            'person_gender' => ['type'=>'VARCHAR','constraint'=>30,'null'=>true],
            'person_age_estimate' => ['type'=>'VARCHAR','constraint'=>30,'null'=>true],
            'person_clothing' => ['type'=>'VARCHAR','constraint'=>255,'null'=>true],
            'person_features' => ['type'=>'VARCHAR','constraint'=>255,'null'=>true],
            'first_response_at' => ['type'=>'DATETIME','null'=>true],
            'status' => ['type'=>'VARCHAR','constraint'=>30,'default'=>'new'],
            'created_at' => ['type'=>'DATETIME','null'=>true], 'updated_at' => ['type'=>'DATETIME','null'=>true],
        ]);
        $this->forge->addKey('id', true); $this->forge->addKey(['store_id','status']); $this->forge->addKey('report_id');
        $this->forge->addForeignKey('report_id','shift_reports','id','CASCADE','CASCADE'); $this->forge->addForeignKey('store_id','stores','id','RESTRICT','CASCADE'); $this->forge->addForeignKey('area_id','areas','id','RESTRICT','CASCADE'); $this->forge->addForeignKey('camera_id','cameras','id','RESTRICT','CASCADE'); $this->forge->addForeignKey('incident_type_id','incident_types','id','RESTRICT','CASCADE');
        $this->forge->createTable('incidents');
        $this->forge->addField(['id'=>['type'=>'BIGINT','constraint'=>20,'unsigned'=>true,'auto_increment'=>true], 'incident_id'=>['type'=>'BIGINT','constraint'=>20,'unsigned'=>true], 'action_name'=>['type'=>'VARCHAR','constraint'=>100], 'note'=>['type'=>'VARCHAR','constraint'=>255,'null'=>true], 'created_at'=>['type'=>'DATETIME','null'=>true]]);
        $this->forge->addKey('id',true); $this->forge->addForeignKey('incident_id','incidents','id','CASCADE','CASCADE'); $this->forge->createTable('incident_actions');
    }
    public function down() { $this->forge->dropTable('incident_actions',true); $this->forge->dropTable('incidents',true); }
}
