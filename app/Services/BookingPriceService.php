<?php
namespace App\Services;
use App\Models\Vehicle;
use Carbon\CarbonInterface;
class BookingPriceService { public function calculate(Vehicle $vehicle, CarbonInterface $start, CarbonInterface $end, bool $useDriver=false, ?string $driverType=null): array { $days=max(1,(int)ceil($start->diffInMinutes($end)/1440)); $base=$vehicle->daily_price*$days; $driver=$useDriver?$vehicle->driver_daily_price*$days*($driverType==='OUT_OF_CITY'?1.5:1):0; $subtotal=$base+$driver; return ['rental_days'=>$days,'base_price'=>$base,'driver_price'=>$driver,'discount_amount'=>0,'subtotal'=>$subtotal,'tax_amount'=>0,'deposit_amount'=>0,'total_amount'=>$subtotal]; } }
