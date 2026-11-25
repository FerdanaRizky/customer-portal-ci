<?php
namespace App\Database\Seeds;
use CodeIgniter\Database\Seeder;use CodeIgniter\CLI\CLI;
class PortalSeeder extends Seeder {public function run(){$u=getenv('ADMIN_USERNAME')?:'admin';$p=getenv('ADMIN_PASSWORD')?:'';if($p===''){CLI::write('ADMIN_PASSWORD belum diatur; admin awal dilewati.','yellow');return;}if(!$this->db->table('admins')->where('username',$u)->countAllResults()){$this->db->table('admins')->insert(['username'=>$u,'password_hash'=>password_hash($p,PASSWORD_DEFAULT),'created_at'=>date('Y-m-d H:i:s')]);CLI::write('Admin awal dibuat: '.$u,'green');}}}