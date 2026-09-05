<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn('person_name');
            $table->foreignId('contact_id')->after('account_id')->constrained()->restrictOnDelete();

            $table->index(['user_id', 'contact_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'contact_id']);
            $table->dropConstrainedForeignId('contact_id');
            $table->string('person_name', 150);
        });
    }
};
