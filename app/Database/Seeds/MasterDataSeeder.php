<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    public function run()
    {
        $now = gmdate('Y-m-d H:i:s');
        $this->seedStores($now);
        $this->seedAreasCamerasAndShifts($now);
        $this->seedIncidentTypes($now);
        $this->seedChecklistTemplate($now);
    }

    private function seedStores(string $now): void
    {
        $stores = [
            ['MK1', 'MK1 Babarsari', 'Babarsari'],
            ['MK2', 'MK2 Simanjuntak', 'Simanjuntak'],
            ['MK3', 'MK3 Supeno', 'Supeno'],
            ['MK4', 'MK4 Palagan', 'Palagan'],
            ['MK5', 'MK5 Godean', 'Godean'],
            ['MK6', 'MK6 Imogiri', 'Imogiri'],
            ['MK7', 'MK7 Keloram', 'Keloram'],
            ['MKM1', 'MK Mini 1 Pelemeswu', 'Pelemeswu'],
            ['MKM2', 'MK Mini 2 Diro', 'Diro'],
            ['MKM3', 'MK Mini 3 Minomartani', 'Minomartani'],
        ];

        $table = $this->db->table('stores');
        foreach ($stores as [$code, $name, $address]) {
            if ($table->where('code', $code)->countAllResults() === 0) {
                $table->insert([
                    'code' => $code,
                    'name' => $name,
                    'address' => $address,
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    private function seedIncidentTypes(string $now): void
    {
        $types = [
            'theft' => 'Pencurian',
            'suspicious-behavior' => 'Perilaku mencurigakan',
            'accident-fall' => 'Kecelakaan/jatuh',
            'property-damage' => 'Kerusakan properti',
            'disturbance' => 'Keributan',
            'suspected-internal-fraud' => 'Dugaan fraud internal',
            'other' => 'Lainnya',
        ];
        $table = $this->db->table('incident_types');
        foreach ($types as $code => $name) {
            if ($table->where('code', $code)->countAllResults() === 0) {
                $table->insert(['code' => $code, 'name' => $name, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now]);
            }
        }
    }

    private function seedAreasCamerasAndShifts(string $now): void
    {
        $areaDefinitions = [
            ['Kasir/POS', 'high'],
            ['Pintu Masuk/Keluar', 'high'],
            ['Gudang', 'high'],
            ['Rak Bernilai Tinggi', 'high'],
            ['Parkir', 'medium'],
            ['Loading Dock', 'medium'],
            ['Koridor Luar Toilet/Ruang Ganti', 'medium'],
        ];
        $shiftDefinitions = [
            ['Pagi', '06:00:00', '14:00:00'],
            ['Sore', '14:00:00', '22:00:00'],
            ['Malam', '22:00:00', '06:00:00'],
        ];

        $stores = $this->db->table('stores')->select('id, code')->where('is_active', 1)->get()->getResultArray();
        $areas = $this->db->table('areas');
        $cameras = $this->db->table('cameras');
        $shifts = $this->db->table('shift_templates');

        foreach ($stores as $store) {
            foreach ($areaDefinitions as $position => [$name, $priority]) {
                $area = $areas->where('store_id', $store['id'])->where('name', $name)->get()->getRowArray();
                if ($area === null) {
                    $areas->insert([
                        'store_id' => $store['id'],
                        'name' => $name,
                        'priority' => $priority,
                        'is_active' => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                    $areaId = (int) $this->db->insertID();
                } else {
                    $areaId = (int) $area['id'];
                }

                $cameraCode = $store['code'] . '-CAM-' . str_pad((string) ($position + 1), 2, '0', STR_PAD_LEFT);
                if ($cameras->where('store_id', $store['id'])->where('code', $cameraCode)->countAllResults() === 0) {
                    $cameras->insert([
                        'store_id' => $store['id'],
                        'area_id' => $areaId,
                        'code' => $cameraCode,
                        'label' => 'CCTV ' . $name,
                        'dvr_channel' => 'CH-' . str_pad((string) ($position + 1), 2, '0', STR_PAD_LEFT),
                        'is_active' => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }

            foreach ($shiftDefinitions as [$name, $startTime, $endTime]) {
                if ($shifts->where('store_id', $store['id'])->where('name', $name)->countAllResults() === 0) {
                    $shifts->insert([
                        'store_id' => $store['id'],
                        'name' => $name,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'monitoring_interval_minutes' => 120,
                        'is_active' => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }

    private function seedChecklistTemplate(string $now): void
    {
        $templates = $this->db->table('checklist_templates');
        $template = $templates->where('name', 'Checklist Operasional CCTV')->where('version', 1)->get()->getRowArray();
        if ($template === null) {
            $templates->insert([
                'name' => 'Checklist Operasional CCTV',
                'version' => 1,
                'description' => 'Checklist standar awal dan akhir shift monitoring CCTV.',
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $templateId = (int) $this->db->insertID();
        } else {
            $templateId = (int) $template['id'];
        }

        if ($this->db->table('checklist_template_items')->where('checklist_template_id', $templateId)->countAllResults() > 0) {
            return;
        }

        $items = [
            ['opening', 1, 'Monitor berfungsi normal.', 1, 0],
            ['opening', 2, 'Jumlah kamera aktif sesuai standar.', 1, 0],
            ['opening', 3, 'Gambar kamera jelas, tidak blur atau terhalang.', 1, 0],
            ['opening', 4, 'Recording normal.', 1, 0],
            ['opening', 5, 'Alarm berfungsi.', 1, 0],
            ['opening', 6, 'HT/telepon berfungsi.', 1, 0],
            ['opening', 7, 'Log sebelumnya telah dibaca.', 1, 0],
            ['opening', 8, 'Area prioritas terpantau.', 1, 0],
            ['closing', 1, 'Rekaman tersimpan baik.', 1, 0],
            ['closing', 2, 'Clipping insiden dilakukan bila ada.', 1, 1],
            ['closing', 3, 'Laporan insiden diserahkan ke supervisor bila ada.', 1, 1],
            ['closing', 4, 'Peralatan dalam kondisi baik.', 1, 0],
            ['closing', 5, 'Log lengkap.', 1, 0],
            ['closing', 6, 'Serah terima dilakukan.', 1, 0],
        ];
        foreach ($items as [$phase, $sortOrder, $text, $required, $allowsNa]) {
            $this->db->table('checklist_template_items')->insert([
                'checklist_template_id' => $templateId,
                'phase' => $phase,
                'item_text' => $text,
                'sort_order' => $sortOrder,
                'is_required' => $required,
                'allows_na' => $allowsNa,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
