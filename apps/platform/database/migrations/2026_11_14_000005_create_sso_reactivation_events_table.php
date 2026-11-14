<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sso_reactivation_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sso_invitation_id')->constrained('sso_invitations')->cascadeOnDelete();
            // The revocation timestamp that was cleared, retained so that
            // reactivation never erases the only record of a prior revocation.
            $table->timestamp('prior_revoked_at');
            $table->string('reason')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sso_reactivation_events');
    }
};
