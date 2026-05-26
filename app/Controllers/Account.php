<?php

namespace App\Controllers;

class Account extends BaseController
{
	function __construct() {
		helper('form');
		$this->validation = \Config\Services::validation();
		$this->Maccount = model('Maccount');
	}

    public function Login() {
    	$data['title'] = "Login Page";

    	if ($this->request->is('post')) {
    		//jika ada lebih dari 1 validasi maka akan di clear dulu untuk membuat validasi baru
            $this->validation->reset();

            $rules = [
                'login-username' => 'required|min_length[3]|alpha_numeric',
                'login-password' => 'required|min_length[7]',
            ];

            $this->validation->setRules($rules);

            $datapost = $this->request->getPost();

            //validasi data inputan
            if (! $this->validation->run($datapost)) {
            	$data['errors'] = $this->validation->getErrors();
                return view('v_login', $data);
            } else {
                $res = $this->Maccount->validateLogin($datapost);

                if ($res["status"]) {
                    return redirect()->route('home');
                } else {
                    $data['errors'] = $res;
                    return view('v_login', $data);
                }
            }

        }

        return view('v_login', $data);
    }

    public function Logout() {
        $this->session->destroy();
        return redirect()->route('/');
    }

}
