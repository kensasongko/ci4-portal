<?php

namespace App\Models;

use CodeIgniter\Model;

class Mapplication extends Model
{
    protected $table      = 'applications';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'name', 'description', 'icon', 'color',
        'sso_login_url', 'local_login_url',
        'is_active', 'sort_order',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getActiveApplications(): array
    {
        return $this->where('is_active', 1)
                    ->orderBy('sort_order', 'ASC')
                    ->orderBy('name', 'ASC')
                    ->findAll();
    }
}
