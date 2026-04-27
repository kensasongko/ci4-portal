<?php
 
namespace App\Controllers;
 
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;

class Userapi extends ResourceController
{
    function __construct() {
        $this->model = model('Muserapi');
    }

    public function index() 
    {
        $result = $this->model->orderBy('id')->findAll();

        if ($result) {
            $data = [
                'status'    => TRUE,
                'message'   => 'Data ditemukan',
                'data'      => $result
            ];

            return $this->respond($data, 200);    
        } else {
            $data = [
                'status'    => FALSE,
                'message'   => 'Data tidak ditemukan',
                'data'      => []
            ];

            return $this->respond($data, 400);    
        }
    }

    public function create() {
        $datareq = $this->request->getVar();

        $res = [
            'username'  => $datareq['username'],
            'password'  => password_hash('12345', PASSWORD_DEFAULT)
        ];

        if ($this->model->insert($res)) {
            $data = [
                'status'    => TRUE,
                'message'   => 'Data berhasil disimpan'
            ];

            return $this->respond($data, 200);
        } else {
            $data = [
                'status'    => FALSE,
                'message'   => 'Data gagal disimpan'
            ];

            return $this->respond($data, 400);
        }
    }

    public function show($id = null) {
        $result = $this->model->where('id', $id)->first();

        if ($result) {
            $data = [
                'status'    => TRUE,
                'message'   => 'Data ditemukan',
                'data'      => $result
            ];

            return $this->respond($data, 200);    
        } else {
            $data = [
                'status'    => FALSE,
                'message'   => 'Data tidak ditemukan',
                'data'      => []
            ];

            return $this->respond($data, 400);    
        }
    }

    public function update($id = null) {
        $datareq = $this->request->getVar();

        $res = [
            'username'  => $datareq['username'],
        ];

        if ($this->model->update($id, $res)) {
            $data = [
                'status'    => TRUE,
                'message'   => 'Data berhasil disimpan'
            ];

            return $this->respond($data, 200);
        } else {
            $data = [
                'status'    => FALSE,
                'message'   => 'Data gagal disimpan'
            ];

            return $this->respond($data, 400);
        }
    }

    public function delete($id = null) {
        // return $this->respond($id, 200);
        if ($this->model->where('id', $id)->delete($id)) {
            $data = [
                'status'    => TRUE,
                'message'   => 'Data berhasil didelete'
            ];

            return $this->respond($data, 200);
        } else {
            $data = [
                'status'    => FALSE,
                'message'   => 'Error'
            ];

            return $this->respond($data, 400);
        }
    }

}

