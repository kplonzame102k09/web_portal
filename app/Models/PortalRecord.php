<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PortalRecord extends Model { protected $fillable=['type','student_id','user_id','created_by','status','record_date','data']; protected function casts():array{return ['data'=>'array','record_date'=>'date'];} public function student(){return $this->belongsTo(User::class,'student_id');} public function author(){return $this->belongsTo(User::class,'created_by');} }
