<?php

namespace App\Services;

use DateTimeImmutable;
use DateTimeZone;

class ShiftReportService
{
    public function createDraft(int $userId, int $storeId, int $shiftId, int $templateId, ?int $supervisorId): int
    {
        $db = db_connect();
        $store = $db->table('stores')->where('id', $storeId)->where('is_active', 1)->get()->getRowArray();
        $shift = $db->table('shift_templates')->where('id', $shiftId)->where('store_id', $storeId)->where('is_active', 1)->get()->getRowArray();
        $template = $db->table('checklist_templates')->where('id', $templateId)->where('is_active', 1)->get()->getRowArray();
        if ($store === null || $shift === null || $template === null) {
            throw new \DomainException('Toko, shift, atau template checklist tidak valid.');
        }

        $items = $db->table('checklist_template_items')->where('checklist_template_id', $templateId)->orderBy('phase')->orderBy('sort_order')->get()->getResultArray();
        if ($items === []) {
            throw new \DomainException('Template checklist belum memiliki item.');
        }

        $timezone = new DateTimeZone('Asia/Jakarta');
        $now = new DateTimeImmutable('now', $timezone);
        $start = new DateTimeImmutable($now->format('Y-m-d') . ' ' . $shift['start_time'], $timezone);
        $end = new DateTimeImmutable($now->format('Y-m-d') . ' ' . $shift['end_time'], $timezone);
        if ($end <= $start) {
            $end = $end->modify('+1 day');
        }

        $db->transStart();
        $nowUtc = gmdate('Y-m-d H:i:s');
        $db->table('shift_reports')->insert([
            'report_number' => 'CCTV-' . $store['code'] . '-' . $now->format('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2))),
            'user_id' => $userId,
            'store_id' => $storeId,
            'shift_template_id' => $shiftId,
            'supervisor_id' => $supervisorId ?: null,
            'checklist_template_id' => $templateId,
            'business_date' => $start->format('Y-m-d'),
            'planned_start_at' => $start->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
            'planned_end_at' => $end->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
            'actual_start_at' => $now->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
            'status' => 'draft',
            'created_at' => $nowUtc,
            'updated_at' => $nowUtc,
        ]);
        $reportId = (int) $db->insertID();
        foreach ($items as $item) {
            $db->table('report_checklist_items')->insert([
                'report_id' => $reportId,
                'source_template_item_id' => $item['id'],
                'phase' => $item['phase'],
                'item_text' => $item['item_text'],
                'sort_order' => $item['sort_order'],
                'is_required' => $item['is_required'],
                'allows_na' => $item['allows_na'],
                'created_at' => $nowUtc,
                'updated_at' => $nowUtc,
            ]);
        }
        $db->transComplete();
        if (! $db->transStatus()) {
            throw new \RuntimeException('Draft laporan tidak dapat dibuat.');
        }

        return $reportId;
    }

    /** @return array<string, mixed>|null */
    public function report(int $reportId): ?array
    {
        return db_connect()->table('shift_reports')
            ->select('shift_reports.*, stores.code AS store_code, stores.name AS store_name, shift_templates.name AS shift_name, shift_templates.monitoring_interval_minutes, officer.name AS officer_name, supervisor.name AS supervisor_name, checklist_templates.name AS checklist_name, checklist_templates.version AS checklist_version')
            ->join('stores', 'stores.id = shift_reports.store_id')
            ->join('shift_templates', 'shift_templates.id = shift_reports.shift_template_id')
            ->join('users AS officer', 'officer.id = shift_reports.user_id')
            ->join('users AS supervisor', 'supervisor.id = shift_reports.supervisor_id', 'left')
            ->join('checklist_templates', 'checklist_templates.id = shift_reports.checklist_template_id')
            ->where('shift_reports.id', $reportId)->get()->getRowArray();
    }

    /** @return list<array<string, mixed>> */
    public function reportsForUser(int $userId): array
    {
        return db_connect()->table('shift_reports')->select('shift_reports.*, stores.name AS store_name, shift_templates.name AS shift_name')->join('stores', 'stores.id = shift_reports.store_id')->join('shift_templates', 'shift_templates.id = shift_reports.shift_template_id')->where('shift_reports.user_id', $userId)->orderBy('shift_reports.created_at', 'DESC')->get()->getResultArray();
    }

    /** @return list<array<string, mixed>> */
    public function items(int $reportId, string $phase): array
    {
        return db_connect()->table('report_checklist_items')->where('report_id', $reportId)->where('phase', $phase)->orderBy('sort_order')->get()->getResultArray();
    }

    /** @param array<string, mixed> $answers @param array<string, mixed> $notes */
    public function saveChecklist(int $reportId, string $phase, array $answers, array $notes, string $sectionNote = ''): void
    {
        $items = $this->items($reportId, $phase);
        $db = db_connect();
        foreach ($items as $item) {
            $answer = $answers[$item['id']] ?? null;
            $note = trim((string) ($notes[$item['id']] ?? ''));
            if (! in_array($answer, ['yes', 'no', 'na'], true) || ($answer === 'na' && ! $item['allows_na']) || ($answer === 'no' && $note === '')) {
                throw new \DomainException('Setiap item wajib dijawab. Jawaban Tidak harus disertai keterangan.');
            }
            $db->table('report_checklist_items')->where('id', $item['id'])->update(['answer' => $answer, 'note' => $note ?: null, 'answered_at' => gmdate('Y-m-d H:i:s'), 'updated_at' => gmdate('Y-m-d H:i:s')]);
        }
        $db->table('shift_reports')->where('id', $reportId)->update([
            $phase === 'opening' ? 'opening_note' : 'closing_note' => trim($sectionNote) ?: null,
            'updated_at' => gmdate('Y-m-d H:i:s'),
        ]);
    }

    /** @param array<string, mixed> $input */
    public function saveHandover(int $reportId, array $input): void
    {
        $senderSignature = $this->validatedSignature((string) ($input['handover_sender_signature'] ?? ''));
        $receiverSignature = $this->validatedSignature((string) ($input['handover_receiver_signature'] ?? ''));
        db_connect()->table('shift_reports')->where('id', $reportId)->update([
            'handover_receiver_name' => trim((string) ($input['handover_receiver_name'] ?? '')) ?: null,
            'handover_sender_time' => trim((string) ($input['handover_sender_time'] ?? '')) ?: null,
            'handover_receiver_time' => trim((string) ($input['handover_receiver_time'] ?? '')) ?: null,
            'handover_note' => trim((string) ($input['handover_note'] ?? '')) ?: null,
            'handover_sender_signature' => $senderSignature,
            'handover_receiver_signature' => $receiverSignature,
            'updated_at' => gmdate('Y-m-d H:i:s'),
        ]);
    }

    private function validatedSignature(string $dataUrl): ?string
    {
        if ($dataUrl === '') return null;
        if (strlen($dataUrl) > 200000 || ! preg_match('#\\Adata:image/png;base64,([A-Za-z0-9+/]+={0,2})\\z#D', $dataUrl, $matches) || base64_decode($matches[1], true) === false) {
            throw new \DomainException('Tanda tangan tidak valid. Silakan gambar ulang.');
        }
        return $dataUrl;
    }

    /** @return list<array<string, mixed>> */
    public function monitoringSlots(array $report): array
    {
        $db = db_connect();
        if ($db->table('monitoring_slots')->where('report_id', $report['id'])->countAllResults() === 0) {
            $start = new DateTimeImmutable($report['planned_start_at'], new DateTimeZone('UTC'));
            $end = new DateTimeImmutable($report['planned_end_at'], new DateTimeZone('UTC'));
            $interval = (int) $report['monitoring_interval_minutes'];
            for ($slot = $start; $slot < $end; $slot = $slot->modify('+' . $interval . ' minutes')) {
                $db->table('monitoring_slots')->insert(['report_id' => $report['id'], 'scheduled_at' => $slot->format('Y-m-d H:i:s'), 'status' => 'pending', 'created_at' => gmdate('Y-m-d H:i:s'), 'updated_at' => gmdate('Y-m-d H:i:s')]);
            }
        }
        return $db->table('monitoring_slots')->select('monitoring_slots.*, monitoring_logs.area_id, monitoring_logs.condition, monitoring_logs.finding_note, monitoring_logs.action_note')->join('monitoring_logs', 'monitoring_logs.monitoring_slot_id = monitoring_slots.id', 'left')->where('monitoring_slots.report_id', $report['id'])->orderBy('scheduled_at')->get()->getResultArray();
    }

    /** @param array<string, mixed> $slots */
    public function saveMonitoring(int $reportId, int $storeId, array $slots): void
    {
        $db = db_connect();
        foreach ($slots as $slotId => $input) {
            $slot = $db->table('monitoring_slots')->where('id', $slotId)->where('report_id', $reportId)->get()->getRowArray();
            if ($slot === null || empty($input['area_id']) || ! in_array($input['condition'] ?? '', ['normal', 'has_finding'], true)) throw new \DomainException('Lengkapi area dan kondisi pada setiap log monitoring.');
            $areaOk = $db->table('areas')->where('id', $input['area_id'])->where('store_id', $storeId)->countAllResults() === 1;
            if (! $areaOk || ($input['condition'] === 'has_finding' && trim((string) ($input['finding_note'] ?? '')) === '')) throw new \DomainException('Temuan wajib diberi uraian dan area harus berasal dari toko laporan.');
            $data = ['area_id' => (int) $input['area_id'], 'condition' => $input['condition'], 'finding_note' => trim((string) ($input['finding_note'] ?? '')) ?: null, 'action_note' => trim((string) ($input['action_note'] ?? '')) ?: null, 'actual_observed_at' => gmdate('Y-m-d H:i:s'), 'updated_at' => gmdate('Y-m-d H:i:s')];
            $existing = $db->table('monitoring_logs')->where('monitoring_slot_id', $slotId)->get()->getRowArray();
            if ($existing) $db->table('monitoring_logs')->where('id', $existing['id'])->update($data); else { $data['monitoring_slot_id'] = $slotId; $data['created_at'] = gmdate('Y-m-d H:i:s'); $db->table('monitoring_logs')->insert($data); }
            $db->table('monitoring_slots')->where('id', $slotId)->update(['status' => 'completed', 'updated_at' => gmdate('Y-m-d H:i:s')]);
        }
    }

    /** @return list<array<string, mixed>> */
    public function incidents(int $reportId): array
    {
        return db_connect()->table('incidents')->select('incidents.*, areas.name AS area_name, cameras.label AS camera_label, incident_types.name AS type_name')->join('areas','areas.id = incidents.area_id')->join('cameras','cameras.id = incidents.camera_id','left')->join('incident_types','incident_types.id = incidents.incident_type_id')->where('report_id',$reportId)->orderBy('occurred_at')->get()->getResultArray();
    }

    /** @param list<array<string, mixed>> $inputs */
    public function addIncidents(int $reportId, array $report, array $inputs): void
    {
        $db = db_connect();
        $prepared = [];
        foreach ($inputs as $input) {
            $areaId = (int) ($input['area_id'] ?? 0);
            $typeId = (int) ($input['incident_type_id'] ?? 0);
            $areaOk = $db->table('areas')->where('id', $areaId)->where('store_id', $report['store_id'])->countAllResults() === 1;
            $cameraOk = empty($input['camera_id']) || $db->table('cameras')->where('id', (int) $input['camera_id'])->where('store_id', $report['store_id'])->where('area_id', $areaId)->countAllResults() === 1;
            $typeOk = $db->table('incident_types')->where('id', $typeId)->where('is_active', 1)->countAllResults() === 1;
            if (! $areaOk || ! $cameraOk || ! $typeOk) throw new \DomainException('Periksa kembali jenis insiden, area, dan kamera pada setiap form.');
            $timeInput = (string) ($input['occurred_time'] ?? '');
            if (! preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $timeInput)) throw new \DomainException('Jam kejadian tidak valid.');
            $response = trim((string) ($input['response_time'] ?? ''));
            if ($response !== '' && ! preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $response)) throw new \DomainException('Waktu respons tidak valid.');
            $prepared[] = [$input, $areaId, $typeId, $timeInput, $response];
        }

        $db->transBegin();
        try {
            $now = gmdate('Y-m-d H:i:s');
            foreach ($prepared as [$input, $areaId, $typeId, $timeInput, $response]) {
                $db->table('incidents')->insert([
                    'report_id' => $reportId, 'store_id' => $report['store_id'], 'area_id' => $areaId,
                    'camera_id' => empty($input['camera_id']) ? null : (int) $input['camera_id'], 'incident_type_id' => $typeId,
                    'occurred_at' => $report['business_date'] . ' ' . $timeInput . ':00', 'description' => trim((string) ($input['description'] ?? '')),
                    'person_gender' => trim((string) ($input['person_gender'] ?? '')) ?: null,
                    'person_age_estimate' => trim((string) ($input['person_age_estimate'] ?? '')) ?: null,
                    'person_clothing' => trim((string) ($input['person_clothing'] ?? '')) ?: null,
                    'person_features' => trim((string) ($input['person_features'] ?? '')) ?: null,
                    'first_response_at' => $response !== '' ? $report['business_date'] . ' ' . $response . ':00' : null,
                    'status' => $input['status'] ?? 'new', 'created_at' => $now, 'updated_at' => $now,
                ]);
                $incidentId = (int) $db->insertID();
                foreach ((array) ($input['actions'] ?? []) as $action) {
                    $db->table('incident_actions')->insert(['incident_id' => $incidentId, 'action_name' => $action, 'created_at' => $now]);
                }
                if (trim((string) ($input['other_action'] ?? '')) !== '') {
                    $db->table('incident_actions')->insert(['incident_id' => $incidentId, 'action_name' => 'Lainnya', 'note' => trim((string) $input['other_action']), 'created_at' => $now]);
                }
            }
            if ($db->transStatus() === false) throw new \RuntimeException('Gagal menyimpan seluruh data insiden.');
            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }
}
