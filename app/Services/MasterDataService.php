<?php

namespace App\Services;

use CodeIgniter\Exceptions\PageNotFoundException;

class MasterDataService
{
    /** @return array<string, mixed> */
    public function definition(string $resource): array
    {
        $definitions = [
            'stores' => [
                'title' => 'Toko', 'singular' => 'toko', 'table' => 'stores', 'permission' => 'stores',
                'fields' => [
                    'code' => ['label' => 'Kode toko', 'type' => 'text', 'rules' => 'required|alpha_dash|max_length[30]'],
                    'name' => ['label' => 'Nama toko', 'type' => 'text', 'rules' => 'required|max_length[150]'],
                    'address' => ['label' => 'Alamat', 'type' => 'textarea', 'rules' => 'permit_empty|max_length[1000]'],
                ],
                'columns' => ['code' => 'Kode', 'name' => 'Nama', 'address' => 'Alamat'],
            ],
            'areas' => [
                'title' => 'Area', 'singular' => 'area', 'table' => 'areas', 'permission' => 'areas',
                'fields' => [
                    'store_id' => ['label' => 'Toko', 'type' => 'select', 'options' => 'stores', 'rules' => 'required|is_natural_no_zero'],
                    'name' => ['label' => 'Nama area', 'type' => 'text', 'rules' => 'required|max_length[150]'],
                    'priority' => ['label' => 'Prioritas', 'type' => 'select', 'options' => ['low' => 'Rendah', 'medium' => 'Sedang', 'high' => 'Tinggi'], 'rules' => 'required|in_list[low,medium,high]'],
                ],
                'columns' => ['store_name' => 'Toko', 'name' => 'Area', 'priority' => 'Prioritas'],
            ],
            'cameras' => [
                'title' => 'Kamera', 'singular' => 'kamera', 'table' => 'cameras', 'permission' => 'cameras',
                'fields' => [
                    'store_id' => ['label' => 'Toko', 'type' => 'select', 'options' => 'stores', 'rules' => 'required|is_natural_no_zero'],
                    'area_id' => ['label' => 'Area', 'type' => 'select', 'options' => 'areas', 'rules' => 'required|is_natural_no_zero'],
                    'code' => ['label' => 'Kode kamera', 'type' => 'text', 'rules' => 'required|alpha_dash|max_length[30]'],
                    'label' => ['label' => 'Label kamera', 'type' => 'text', 'rules' => 'required|max_length[150]'],
                    'dvr_channel' => ['label' => 'Referensi channel DVR/NVR', 'type' => 'text', 'rules' => 'permit_empty|max_length[100]'],
                ],
                'columns' => ['store_name' => 'Toko', 'area_name' => 'Area', 'code' => 'Kode', 'label' => 'Label', 'dvr_channel' => 'Channel'],
            ],
            'shifts' => [
                'title' => 'Shift', 'singular' => 'shift', 'table' => 'shift_templates', 'permission' => 'shifts',
                'fields' => [
                    'store_id' => ['label' => 'Toko', 'type' => 'select', 'options' => 'stores', 'rules' => 'required|is_natural_no_zero'],
                    'name' => ['label' => 'Nama shift', 'type' => 'text', 'rules' => 'required|max_length[100]'],
                    'start_time' => ['label' => 'Jam mulai', 'type' => 'time', 'rules' => 'required|max_length[8]'],
                    'end_time' => ['label' => 'Jam selesai', 'type' => 'time', 'rules' => 'required|max_length[8]'],
                    'monitoring_interval_minutes' => ['label' => 'Interval monitoring', 'type' => 'select', 'options' => ['60' => '60 menit', '120' => '120 menit'], 'rules' => 'required|in_list[60,120]'],
                ],
                'columns' => ['store_name' => 'Toko', 'name' => 'Shift', 'start_time' => 'Mulai', 'end_time' => 'Selesai', 'monitoring_interval_minutes' => 'Interval'],
            ],
            'incident-types' => [
                'title' => 'Jenis Insiden', 'singular' => 'jenis insiden', 'table' => 'incident_types', 'permission' => 'incident_types',
                'fields' => [
                    'code' => ['label' => 'Kode', 'type' => 'text', 'rules' => 'required|alpha_dash|max_length[50]'],
                    'name' => ['label' => 'Nama jenis insiden', 'type' => 'text', 'rules' => 'required|max_length[150]'],
                ],
                'columns' => ['code' => 'Kode', 'name' => 'Nama'],
            ],
            'checklists' => [
                'title' => 'Template Checklist', 'singular' => 'template checklist', 'table' => 'checklist_templates', 'permission' => 'checklists',
                'fields' => [
                    'name' => ['label' => 'Nama template', 'type' => 'text', 'rules' => 'required|max_length[150]'],
                    'version' => ['label' => 'Versi', 'type' => 'number', 'rules' => 'required|is_natural_no_zero'],
                    'description' => ['label' => 'Deskripsi', 'type' => 'textarea', 'rules' => 'permit_empty|max_length[1000]'],
                ],
                'columns' => ['name' => 'Nama', 'version' => 'Versi', 'description' => 'Deskripsi'],
            ],
        ];

        if (! isset($definitions[$resource])) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $definitions[$resource];
    }

    /** @return list<array<string, mixed>> */
    public function all(string $resource): array
    {
        $definition = $this->definition($resource);
        $db = db_connect();

        return match ($resource) {
            'incident-types' => $db->table('incident_types')->select('id, code, name, is_active')->orderBy('name')->get()->getResultArray(),
            'areas' => $db->table('areas')->select('areas.*, stores.name AS store_name')->join('stores', 'stores.id = areas.store_id')->orderBy('stores.name')->orderBy('areas.name')->get()->getResultArray(),
            'cameras' => $db->table('cameras')->select('cameras.*, stores.name AS store_name, areas.name AS area_name')->join('stores', 'stores.id = cameras.store_id')->join('areas', 'areas.id = cameras.area_id')->orderBy('stores.name')->orderBy('cameras.code')->get()->getResultArray(),
            'shifts' => $db->table('shift_templates')->select('shift_templates.*, stores.name AS store_name')->join('stores', 'stores.id = shift_templates.store_id')->orderBy('stores.name')->orderBy('shift_templates.start_time')->get()->getResultArray(),
            default => $db->table($definition['table'])->orderBy('name')->get()->getResultArray(),
        };
    }

    /** @return array<string, mixed>|null */
    public function find(string $resource, int $id): ?array
    {
        $definition = $this->definition($resource);

        return db_connect()->table($definition['table'])->where('id', $id)->get()->getRowArray();
    }

    /** @return array<string, string> */
    public function options(string $source): array
    {
        return match ($source) {
            'stores' => array_column(db_connect()->table('stores')->select('id, name')->where('is_active', 1)->orderBy('name')->get()->getResultArray(), 'name', 'id'),
            'areas' => array_column(db_connect()->table('areas')->select('id, name')->where('is_active', 1)->orderBy('name')->get()->getResultArray(), 'name', 'id'),
            default => [],
        };
    }

    /** @return array<string, string> */
    public function fieldOptions(array $definition): array
    {
        $options = [];
        foreach ($definition['fields'] as $name => $field) {
            if (($field['type'] ?? '') === 'select') {
                $options[$name] = is_string($field['options']) ? $this->options($field['options']) : $field['options'];
            }
        }

        return $options;
    }

    /** @param array<string, mixed> $input */
    public function save(string $resource, array $input, ?int $id = null): void
    {
        $definition = $this->definition($resource);
        $data = [];
        foreach ($definition['fields'] as $field => $config) {
            $data[$field] = is_string($input[$field] ?? null) ? trim($input[$field]) : $input[$field] ?? null;
        }

        if (isset($data['code'])) {
            $data['code'] = strtoupper($data['code']);
        }

        if ($resource === 'areas' && preg_match('/^(toilet|restroom|ruang ganti|changing room)\b/i', $data['name']) === 1) {
            throw new \DomainException('Area privat seperti toilet atau ruang ganti tidak boleh dijadikan area kamera.');
        }

        if ($resource === 'cameras' && ! $this->areaBelongsToStore((int) $data['area_id'], (int) $data['store_id'])) {
            throw new \DomainException('Area kamera harus berasal dari toko yang dipilih.');
        }

        if ($resource === 'shifts' && (! $this->isValidTime($data['start_time']) || ! $this->isValidTime($data['end_time']))) {
            throw new \DomainException('Jam mulai dan jam selesai harus menggunakan format waktu yang valid.');
        }

        $table = db_connect()->table($definition['table']);
        $now = gmdate('Y-m-d H:i:s');
        if ($id === null) {
            $data['is_active'] = 1;
            $data['created_at'] = $now;
            $data['updated_at'] = $now;
            $table->insert($data);

            return;
        }

        $data['updated_at'] = $now;
        $table->where('id', $id)->update($data);
    }

    public function deactivate(string $resource, int $id): void
    {
        $definition = $this->definition($resource);
        db_connect()->table($definition['table'])->where('id', $id)->update([
            'is_active' => 0,
            'updated_at' => gmdate('Y-m-d H:i:s'),
        ]);
    }

    /** @return list<array<string, mixed>> */
    public function checklistItems(int $templateId): array
    {
        return db_connect()->table('checklist_template_items')->where('checklist_template_id', $templateId)->orderBy('phase')->orderBy('sort_order')->get()->getResultArray();
    }

    /** @param array<string, mixed> $input */
    public function addChecklistItem(int $templateId, array $input): void
    {
        db_connect()->table('checklist_template_items')->insert([
            'checklist_template_id' => $templateId,
            'phase' => $input['phase'],
            'item_text' => trim($input['item_text']),
            'sort_order' => (int) $input['sort_order'],
            'is_required' => isset($input['is_required']) ? 1 : 0,
            'allows_na' => isset($input['allows_na']) ? 1 : 0,
            'created_at' => gmdate('Y-m-d H:i:s'),
            'updated_at' => gmdate('Y-m-d H:i:s'),
        ]);
    }

    private function areaBelongsToStore(int $areaId, int $storeId): bool
    {
        return db_connect()->table('areas')->where('id', $areaId)->where('store_id', $storeId)->countAllResults() === 1;
    }

    private function isValidTime(string $value): bool
    {
        return preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $value) === 1;
    }
}
