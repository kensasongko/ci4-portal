<?php

namespace App\Models;
use CodeIgniter\Model;

class Maccount extends Model {
	function __construct() {
		$this->db = \Config\Database::connect();
		$this->session = \Config\Services::session();
	}

	function validateLogin($data) {
		$builder = $this->db->table('user');

		$builder->select("username, password");
		$builder->where("username", $data["login-username"]);
		$row = $builder->get();

		if ($row->getNumRows() == 0) {
			$res["status"] = false;
			$res["login-username"] = "Username not found.";
		} else {
			$user = $row->getRow();

			if (password_verify($data["login-password"], $user->password)) {
				unset($user->password);
				$xuser = $row->getResultArray();

				$userData = [
					'user' => $xuser[0],
					'logged_in' => TRUE
				];

				$this->session->set($userData);

				$res["status"] = true;
				$res["responMsg"] = "Login berhasil.";
			} else {
				$res["status"] = false;
				$res["login-password"] = "Invalid password.";
			}
		}

		return $res;
	}
}

?>