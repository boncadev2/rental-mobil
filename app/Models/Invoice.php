<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model; class Invoice extends Model {protected $fillable=['booking_id','invoice_number','total','paid_amount','balance','status'];}
