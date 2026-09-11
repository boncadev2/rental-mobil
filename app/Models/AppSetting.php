<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;class AppSetting extends Model{protected $fillable=['key','value'];public static function values():array{return self::query()->pluck('value','key')->all();}}
