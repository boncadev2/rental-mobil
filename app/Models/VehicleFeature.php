<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class VehicleFeature extends Model { protected $fillable=['name','icon']; public function vehicles(): BelongsToMany { return $this->belongsToMany(Vehicle::class, 'vehicle_feature_pivot'); } }
