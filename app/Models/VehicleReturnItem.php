<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;class VehicleReturnItem extends Model{public $timestamps=false;protected $fillable=['checklist_code','checklist_name','condition_status','notes'];}
