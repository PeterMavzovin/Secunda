<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Kalnoy\Nestedset\NestedSet;

return new class extends Migration
{    
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            // добавляем колонки nested set
            NestedSet::columns($table);
        }); 
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            // откат
            NestedSet::dropColumns($table);
        });
    }
};