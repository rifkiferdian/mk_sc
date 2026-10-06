<?php

namespace App\Controllers;

use App\Exceptions\ForbiddenException;
use App\Services\MasterDataService;
use App\Services\PermissionService;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;

class MasterDataController extends BaseController
{
    public function index(string $resource): string
    {
        $definition = $this->definition($resource);
        $this->authorize($definition['permission'] . '.view');

        return view('master/index', [
            'definition' => $definition,
            'resource' => $resource,
            'records' => $this->master()->all($resource),
            'showMasterNavigation' => true,
        ]);
    }

    public function create(string $resource): string
    {
        $definition = $this->definition($resource);
        $this->authorize($definition['permission'] . '.create');

        return view('master/form', [
            'definition' => $definition,
            'resource' => $resource,
            'record' => [],
            'options' => $this->master()->fieldOptions($definition),
            'showMasterNavigation' => true,
        ]);
    }

    public function store(string $resource): RedirectResponse
    {
        return $this->persist($resource);
    }

    public function edit(string $resource, int $id): string
    {
        $definition = $this->definition($resource);
        $this->authorize($definition['permission'] . '.update');
        $record = $this->master()->find($resource, $id);
        if ($record === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return view('master/form', [
            'definition' => $definition,
            'resource' => $resource,
            'record' => $record,
            'options' => $this->master()->fieldOptions($definition),
            'showMasterNavigation' => true,
        ]);
    }

    public function update(string $resource, int $id): RedirectResponse
    {
        return $this->persist($resource, $id);
    }

    public function deactivate(string $resource, int $id): RedirectResponse
    {
        $definition = $this->definition($resource);
        $this->authorize($definition['permission'] . '.deactivate');
        if ($this->master()->find($resource, $id) === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $this->master()->deactivate($resource, $id);

        return redirect()->to(site_url('master/' . $resource))->with('success', ucfirst($definition['singular']) . ' berhasil dinonaktifkan.');
    }

    public function checklistItems(int $templateId): string
    {
        $definition = $this->definition('checklists');
        $this->authorize($definition['permission'] . '.view');
        $template = $this->master()->find('checklists', $templateId);
        if ($template === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return view('master/checklist_items', [
            'template' => $template,
            'items' => $this->master()->checklistItems($templateId),
            'showMasterNavigation' => true,
        ]);
    }

    public function addChecklistItem(int $templateId): RedirectResponse
    {
        $this->authorize('checklists.update');
        if ($this->master()->find('checklists', $templateId) === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $rules = [
            'phase' => 'required|in_list[opening,closing]',
            'item_text' => 'required|max_length[255]',
            'sort_order' => 'required|is_natural_no_zero',
        ];
        if (! $this->validateData($this->request->getPost(), $rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            $this->master()->addChecklistItem($templateId, $this->request->getPost());
        } catch (\Throwable) {
            return redirect()->back()->withInput()->with('error', 'Item tidak dapat disimpan. Pastikan urutan item belum digunakan pada bagian yang sama.');
        }

        return redirect()->to(site_url('master/checklist-templates/' . $templateId . '/items'))->with('success', 'Item checklist berhasil ditambahkan.');
    }

    /** @return array<string, mixed> */
    private function definition(string $resource): array
    {
        return $this->master()->definition($resource);
    }

    private function persist(string $resource, ?int $id = null): RedirectResponse
    {
        $definition = $this->definition($resource);
        $this->authorize($definition['permission'] . ($id === null ? '.create' : '.update'));
        if ($id !== null && $this->master()->find($resource, $id) === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $rules = [];
        foreach ($definition['fields'] as $field => $config) {
            $rules[$field] = $config['rules'];
        }
        if (! $this->validateData($this->request->getPost(), $rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            $this->master()->save($resource, $this->request->getPost(), $id);
        } catch (\DomainException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        } catch (\Throwable) {
            return redirect()->back()->withInput()->with('error', 'Data tidak dapat disimpan. Pastikan nilai unik dan relasinya sudah benar.');
        }

        $message = ucfirst($definition['singular']) . ($id === null ? ' berhasil ditambahkan.' : ' berhasil diperbarui.');

        return redirect()->to(site_url('master/' . $resource))->with('success', $message);
    }

    private function authorize(string $permission): void
    {
        if (! (new PermissionService())->can((int) session('auth_user_id'), $permission)) {
            throw ForbiddenException::forPermission();
        }
    }

    private function master(): MasterDataService
    {
        return new MasterDataService();
    }
}
