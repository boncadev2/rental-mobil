<?php
use Illuminate\Database\Migrations\Migration;use Illuminate\Database\Schema\Blueprint;use Illuminate\Support\Facades\Schema;
return new class extends Migration{public function up():void{Schema::table('bookings',fn(Blueprint $t)=>$t->string('driver_type')->nullable()->after('use_driver'));}public function down():void{Schema::table('bookings',fn(Blueprint $t)=>$t->dropColumn('driver_type'));}};
