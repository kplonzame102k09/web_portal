<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory; use Illuminate\Foundation\Auth\User as Authenticatable; use Illuminate\Notifications\Notifiable;
class User extends Authenticatable { use HasFactory, Notifiable; protected $fillable=['portal_id','first_name','middle_name','last_name','email','contact','role','department','photo','password']; protected $hidden=['password','remember_token']; protected function casts():array{return ['email_verified_at'=>'datetime','password'=>'hashed'];} public function getNameAttribute():string{return trim(collect([$this->first_name,$this->middle_name,$this->last_name])->filter()->join(' '));} }
