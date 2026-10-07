<?php
namespace App\Services;use App\Models\ActivityLog;use Illuminate\Database\Eloquent\Model;use Illuminate\Http\Request;
class ActivityLogService{public function record(string $action,Model $subject,?Request $request=null,array $old=[],array $new=[]):ActivityLog{return ActivityLog::create(['user_id'=>$request?->user()?->id,'action'=>$action,'subject_type'=>$subject::class,'subject_id'=>$subject->getKey(),'old_values'=>$old?:null,'new_values'=>$new?:null,'ip_address'=>$request?->ip(),'user_agent'=>$request?->userAgent()]);}}
