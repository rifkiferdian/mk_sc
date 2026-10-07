<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddHandoverSignaturesToShiftReports extends Migration
{
    public function up()
    {
        $this->forge->addColumn('shift_reports', [
            'handover_sender_signature' => ['type' => 'MEDIUMTEXT', 'null' => true],
            'handover_receiver_signature' => ['type' => 'MEDIUMTEXT', 'null' => true],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('shift_reports', [
            'handover_sender_signature',
            'handover_receiver_signature',
        ]);
    }
}
