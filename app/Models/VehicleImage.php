<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class VehicleImage extends Model { protected $fillable=['file_path','is_primary','sort_order']; protected $casts=['is_primary'=>'boolean']; public function vehicle(): BelongsTo { return $this->belongsTo(Vehicle::class); } }
