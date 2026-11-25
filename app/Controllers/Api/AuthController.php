<?php
namespace App\Controllers\Api;
use App\Controllers\BaseController; use App\Libraries\WhatsappGateway;
class AuthController extends BaseController {
 public function requestOtp(){
  $d=$this->request->getJSON(true)?:$this->request->getPost();$phone=preg_replace('/[^0-9]/','',(string)($d['phone']??''));
  if(strlen($phone)<9||strlen($phone)>15)return $this->json(['success'=>false,'message'=>'Nomor WhatsApp tidak valid.'],422);
  $db=db_connect();$c=$db->table('customers')->where('phone',$phone)->where('is_active',1)->get()->getRowArray();if(!$c)return $this->json(['success'=>false,'message'=>'Nomor belum terdaftar.'],404);
  if($db->table('otp_codes')->where('phone',$phone)->where('created_at >',date('Y-m-d H:i:s',time()-60))->countAllResults())return $this->json(['success'=>false,'message'=>'Tunggu 60 detik sebelum meminta OTP lagi.'],429);
  $otp=(string)random_int(100000,999999);$db->table('otp_codes')->insert(['phone'=>$phone,'otp_hash'=>password_hash($otp,PASSWORD_DEFAULT),'expires_at'=>date('Y-m-d H:i:s',time()+300),'attempts'=>0,'created_at'=>date('Y-m-d H:i:s')]);
  try{(new WhatsappGateway())->sendText($phone,'Kode OTP Customer Portal Anda: '.$otp.'. Berlaku 5 menit. Jangan bagikan kode ini kepada siapa pun.');}catch(\Throwable $e){log_message('error',$e->getMessage());return $this->json(['success'=>false,'message'=>'Gateway WhatsApp belum siap. Hubungi administrator.'],503);}
  return $this->json(['success'=>true,'message'=>'OTP dikirim ke WhatsApp.','expiresIn'=>300]);
 }
 public function verifyOtp(){
  $d=$this->request->getJSON(true)?:$this->request->getPost();$phone=preg_replace('/[^0-9]/','',(string)($d['phone']??''));$otp=preg_replace('/\D/','',(string)($d['otp']??''));$db=db_connect();$r=$db->table('otp_codes')->where('phone',$phone)->orderBy('id','DESC')->get()->getRowArray();
  if(!$r||strtotime($r['expires_at'])<time()||(int)$r['attempts']>=3||!password_verify($otp,$r['otp_hash'])){if($r)$db->table('otp_codes')->where('id',$r['id'])->update(['attempts'=>(int)$r['attempts']+1]);return $this->json(['success'=>false,'message'=>'OTP tidak valid atau kedaluwarsa.'],422);}
  $c=$db->table('customers')->where('phone',$phone)->where('is_active',1)->get()->getRowArray();if(!$c)return $this->json(['success'=>false,'message'=>'Akun pelanggan tidak aktif.'],403);
  session()->regenerate(true);session()->set('customer',['id'=>$c['id'],'phone'=>$phone,'name'=>$c['customer_name'],'deviceId'=>$c['genieacs_device_id']]);$db->table('otp_codes')->where('id',$r['id'])->delete();return $this->json(['success'=>true,'redirect'=>'/dashboard']);
 }
 public function logout(){session()->remove('customer');session()->regenerate(true);return $this->json(['success'=>true,'redirect'=>'/']);}
}