<?php

namespace App\Controllers;

use App\Models\Mapplication;
use CodeIgniter\Exceptions\PageNotFoundException;

class Applications extends BaseController
{
    private Mapplication $model;

    public function __construct()
    {
        $this->model = new Mapplication();
    }

    public function index(): string
    {
        $applications = $this->model
            ->orderBy('sort_order', 'ASC')
            ->orderBy('name', 'ASC')
            ->findAll();

        return view('applications/index', [
            'applications' => $applications,
            'user'         => session('user'),
        ]);
    }

    public function create(): string
    {
        return view('applications/form', [
            'application' => null,
            'errors'      => session()->getFlashdata('errors') ?? [],
            'old'         => session()->getFlashdata('old') ?? [],
            'user'        => session('user'),
        ]);
    }

    public function store()
    {
        $post = $this->request->getPost();

        if (! $this->validate($this->validationRules())) {
            return redirect()->back()->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $this->model->insert($this->collectFields($post));

        return redirect()->to('applications')->with('success', 'Application created.');
    }

    public function edit(int $id): string
    {
        $application = $this->model->find($id);

        if ($application === null) {
            throw PageNotFoundException::forPageNotFound("Application #{$id} not found.");
        }

        return view('applications/form', [
            'application' => $application,
            'errors'      => session()->getFlashdata('errors') ?? [],
            'old'         => session()->getFlashdata('old') ?? [],
            'user'        => session('user'),
        ]);
    }

    public function update(int $id)
    {
        if ($this->model->find($id) === null) {
            throw PageNotFoundException::forPageNotFound("Application #{$id} not found.");
        }

        $post = $this->request->getPost();

        if (! $this->validate($this->validationRules())) {
            return redirect()->back()->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $this->model->update($id, $this->collectFields($post));

        return redirect()->to('applications')->with('success', 'Application updated.');
    }

    public function delete(int $id)
    {
        if ($this->model->find($id) === null) {
            throw PageNotFoundException::forPageNotFound("Application #{$id} not found.");
        }

        $this->model->delete($id);

        return redirect()->to('applications')->with('success', 'Application deleted.');
    }

    public function toggleStatus(int $id)
    {
        $application = $this->model->find($id);

        if ($application === null) {
            throw PageNotFoundException::forPageNotFound("Application #{$id} not found.");
        }

        $this->model->update($id, ['is_active' => (int) ! $application['is_active']]);

        return redirect()->to('applications');
    }

    private function validationRules(): array
    {
        return [
            'name'            => 'required|max_length[100]',
            'description'     => 'permit_empty|max_length[255]',
            'icon'            => 'permit_empty|max_length[80]',
            'color'           => 'permit_empty|in_list[primary,success,danger,warning,info,secondary,dark]',
            'sso_login_url'   => 'permit_empty|max_length[500]',
            'local_login_url' => 'permit_empty|max_length[500]',
            'sort_order'      => 'permit_empty|integer',
        ];
    }

    private function collectFields(array $post): array
    {
        return [
            'name'            => $post['name'],
            'description'     => $post['description'] ?? null,
            'icon'            => $post['icon'] ?? null,
            'color'           => $post['color'] ?: 'primary',
            'sso_login_url'   => $post['sso_login_url'] ?? null,
            'local_login_url' => $post['local_login_url'] ?? null,
            'is_active'       => isset($post['is_active']) ? 1 : 0,
            'sort_order'      => (int) ($post['sort_order'] ?? 0),
        ];
    }
}
