<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;class ActivityLog extends Model{public $timestamps=false;protected $fillable=['user_id','action','subject_type','subject_id','old_values','new_values','ip_address','user_agent'];protected $casts=['old_values'=>'array','new_values'=>'array'];}
