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

	/**
	 * Look up an existing user by Entra `oid`, or create one (JIT) if allowed.
	 * Returns the user row as an associative array, or null if rejected.
	 *
	 * @param array $claims Normalized claims from AzureAuthService
	 * @param bool  $jit    If true, insert a row when no match is found
	 * @return array|null
	 */
	function findOrCreateByAzureOid(array $claims, bool $jit = true): ?array
	{
		$builder = $this->db->table('user');

		$row = $builder->where('azure_oid', $claims['oid'])->get()->getRowArray();
		if ($row !== null) {
			$builder->where('id', $row['id'])->update([
				'last_login_at' => date('Y-m-d H:i:s'),
				'email'         => $claims['email'] ?? $row['email'] ?? null,
				'name'          => $claims['name']  ?? $row['name']  ?? null,
			]);
			return $row;
		}

		// Fall back: link by username/email if a local account already exists.
		if (! empty($claims['username'])) {
			$existing = $this->db->table('user')->where('username', $claims['username'])->get()->getRowArray();
			if ($existing !== null) {
				$this->db->table('user')->where('id', $existing['id'])->update([
					'azure_oid'     => $claims['oid'],
					'auth_source'   => 'azure',
					'email'         => $claims['email'] ?? $existing['email'] ?? null,
					'name'          => $claims['name']  ?? $existing['name']  ?? null,
					'last_login_at' => date('Y-m-d H:i:s'),
				]);
				$existing['azure_oid']     = $claims['oid'];
				$existing['auth_source']   = 'azure';
				return $existing;
			}
		}

		if (! $jit) {
			return null;
		}

		$insert = [
			'username'      => $claims['username'] ?? $claims['email'] ?? $claims['oid'],
			'email'         => $claims['email'] ?? null,
			'name'          => $claims['name']  ?? null,
			'azure_oid'     => $claims['oid'],
			'auth_source'   => 'azure',
			'last_login_at' => date('Y-m-d H:i:s'),
		];
		$this->db->table('user')->insert($insert);
		$insert['id'] = $this->db->insertID();

		return $insert;
	}
}
