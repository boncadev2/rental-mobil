<?php
use Illuminate\Database\Migrations\Migration;use Illuminate\Database\Schema\Blueprint;use Illuminate\Support\Facades\Schema;
return new class extends Migration{public function up():void{Schema::create('vehicle_return_items',function(Blueprint $t){$t->id();$t->foreignId('vehicle_return_id')->constrained()->cascadeOnDelete();$t->string('checklist_code');$t->string('checklist_name');$t->string('condition_status');$t->text('notes')->nullable();});}public function down():void{Schema::dropIfExists('vehicle_return_items');}};
