<?php

namespace App\Controllers;

use App\Exceptions\ForbiddenException;
use App\Services\PermissionService;
use App\Services\ShiftReportService;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;

class ReportController extends BaseController
{
    public function index(): string
    {
        $this->authorize('reports.view_own');
        return view('reports/index', [
            'reports' => $this->reports()->reportsForUser((int) session('auth_user_id')),
            'showReportsNavigation' => true,
            'showMasterNavigation' => $this->canManageMasterData(),
        ]);
    }

    public function create(): string
    {
        $this->authorize('reports.create');
        $db = db_connect();
        return view('reports/create', [
            'stores' => $db->table('stores')->where('is_active', 1)->orderBy('name')->get()->getResultArray(),
            'shifts' => $db->table('shift_templates')->select('shift_templates.*, stores.name AS store_name')->join('stores', 'stores.id = shift_templates.store_id')->where('shift_templates.is_active', 1)->orderBy('stores.name')->orderBy('start_time')->get()->getResultArray(),
            'templates' => $db->table('checklist_templates')->where('is_active', 1)->orderBy('name')->get()->getResultArray(),
            'supervisors' => $db->table('users')->select('id, name, username')->where('is_active', 1)->orderBy('name')->get()->getResultArray(),
            'showReportsNavigation' => true,
            'showMasterNavigation' => $this->canManageMasterData(),
        ]);
    }

    public function store(): RedirectResponse
    {
        $this->authorize('reports.create');
        $rules = ['store_id' => 'required|is_natural_no_zero', 'shift_template_id' => 'required|is_natural_no_zero', 'checklist_template_id' => 'required|is_natural_no_zero', 'supervisor_id' => 'permit_empty|is_natural_no_zero'];
        if (! $this->validateData($this->request->getPost(), $rules)) return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        try {
            $id = $this->reports()->createDraft((int) session('auth_user_id'), (int) $this->request->getPost('store_id'), (int) $this->request->getPost('shift_template_id'), (int) $this->request->getPost('checklist_template_id'), $this->request->getPost('supervisor_id') ? (int) $this->request->getPost('supervisor_id') : null);
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
        return redirect()->to(site_url('reports/' . $id))->with('success', 'Draft laporan berhasil dibuat.');
    }

    public function show(int $id): string
    {
        $this->authorize('reports.view_own');
        $report = $this->reports()->report($id);
        if ($report === null || ((int) $report['user_id'] !== (int) session('auth_user_id') && ! (new PermissionService())->can((int) session('auth_user_id'), 'reports.view_all'))) throw PageNotFoundException::forPageNotFound();
        return view('reports/show', [
            'report' => $report,
            'openingItems' => $this->reports()->items($id, 'opening'),
            'closingItems' => $this->reports()->items($id, 'closing'),
            'monitoringSlots' => $this->reports()->monitoringSlots($report),
            'areas' => db_connect()->table('areas')->where('store_id', $report['store_id'])->where('is_active', 1)->orderBy('name')->get()->getResultArray(),
            'cameras' => db_connect()->table('cameras')->where('store_id', $report['store_id'])->where('is_active', 1)->orderBy('label')->get()->getResultArray(),
            'incidentTypes' => db_connect()->table('incident_types')->where('is_active',1)->orderBy('name')->get()->getResultArray(),
            'incidents' => $this->reports()->incidents($id),
            'showReportsNavigation' => true,
            'showMasterNavigation' => $this->canManageMasterData(),
        ]);
    }

    public function saveChecklist(int $id, string $phase): RedirectResponse
    {
        $this->authorize('reports.update_own');
        if (! in_array($phase, ['opening', 'closing'], true)) throw PageNotFoundException::forPageNotFound();
        $report = $this->reports()->report($id);
        if ($report === null || (int) $report['user_id'] !== (int) session('auth_user_id')) throw PageNotFoundException::forPageNotFound();
        try { $this->reports()->saveChecklist($id, $phase, (array) $this->request->getPost('answers'), (array) $this->request->getPost('notes'), (string) $this->request->getPost('section_note')); }
        catch (\Throwable $e) { return redirect()->back()->withInput()->with('error', $e->getMessage()); }
        return redirect()->to(site_url('reports/' . $id) . '#' . $phase)->with('success', 'Checklist ' . ($phase === 'opening' ? 'awal' : 'akhir') . ' tersimpan.');
    }
    public function saveMonitoring(int $id): RedirectResponse
    {
        $this->authorize('monitoring.create'); $report = $this->reports()->report($id);
        if ($report === null || (int) $report['user_id'] !== (int) session('auth_user_id')) throw PageNotFoundException::forPageNotFound();
        try { $this->reports()->saveMonitoring($id, (int) $report['store_id'], (array) $this->request->getPost('slots')); } catch (\Throwable $e) { return redirect()->back()->withInput()->with('error', $e->getMessage()); }
        return redirect()->to(site_url('reports/' . $id) . '#monitoring')->with('success', 'Log monitoring tersimpan.');
    }
    public function addIncident(int $id): RedirectResponse
    {
        $this->authorize('incidents.create'); $report=$this->reports()->report($id); if ($report===null || (int)$report['user_id'] !== (int)session('auth_user_id')) throw PageNotFoundException::forPageNotFound();
        $incidents = $this->request->getPost('incidents');
        if (! is_array($incidents) || $incidents === []) return redirect()->back()->withInput()->with('error','Isi minimal satu form insiden.');
        foreach ($incidents as $incident) {
            if (! is_array($incident)) return redirect()->back()->withInput()->with('error','Data insiden tidak valid.');
            $rules=['occurred_time'=>'required','area_id'=>'required|is_natural_no_zero','incident_type_id'=>'required|is_natural_no_zero','description'=>'required|max_length[2000]','status'=>'required|in_list[new,in_progress,forwarded,resolved]'];
            if (! $this->validateData($incident,$rules)) return redirect()->back()->withInput()->with('error','Lengkapi jam, jenis, area, deskripsi, dan status pada setiap insiden.');
        }
        try { $this->reports()->addIncidents($id,$report,array_values($incidents)); } catch (\Throwable $e) { return redirect()->back()->withInput()->with('error',$e->getMessage()); }
        return redirect()->to(site_url('reports/'.$id).'#incidents')->with('success','Semua insiden berhasil dicatat.');
    }

    public function saveHandover(int $id): RedirectResponse
    {
        $this->authorize('reports.update_own');
        $report = $this->reports()->report($id);
        if ($report === null || (int) $report['user_id'] !== (int) session('auth_user_id')) throw PageNotFoundException::forPageNotFound();
        $rules = [
            'handover_receiver_name' => 'permit_empty|max_length[150]',
            'handover_sender_time' => 'permit_empty|regex_match[/^(?:[01]\\d|2[0-3]):[0-5]\\d$/]',
            'handover_receiver_time' => 'permit_empty|regex_match[/^(?:[01]\\d|2[0-3]):[0-5]\\d$/]',
            'handover_note' => 'permit_empty|max_length[3000]',
        ];
        if (! $this->validateData($this->request->getPost(), $rules)) return redirect()->back()->withInput()->with('error', 'Periksa kembali nama, jam, dan catatan serah terima.');
        try { $this->reports()->saveHandover($id, $this->request->getPost()); }
        catch (\Throwable $e) { return redirect()->back()->withInput()->with('error', $e->getMessage()); }
        return redirect()->to(site_url('reports/' . $id) . '#handover')->with('success', 'Serah terima shift berhasil disimpan.');
    }

    private function authorize(string $permission): void { if (! (new PermissionService())->can((int) session('auth_user_id'), $permission)) throw ForbiddenException::forPermission(); }
    private function reports(): ShiftReportService { return new ShiftReportService(); }
    private function canManageMasterData(): bool { return (new PermissionService())->can((int) session('auth_user_id'), 'stores.view'); }
}
