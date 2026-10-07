<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddHandoverFieldsToShiftReports extends Migration
{
    public function up()
    {
        $this->forge->addColumn('shift_reports', [
            'handover_receiver_name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'handover_sender_time' => ['type' => 'TIME', 'null' => true],
            'handover_receiver_time' => ['type' => 'TIME', 'null' => true],
            'handover_note' => ['type' => 'TEXT', 'null' => true],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('shift_reports', [
            'handover_receiver_name',
            'handover_sender_time',
            'handover_receiver_time',
            'handover_note',
        ]);
    }
}
