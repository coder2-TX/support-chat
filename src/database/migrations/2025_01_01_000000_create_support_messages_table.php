<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // مهم: حتى لا ينكسر في مشروع قديم (مثل طابور) لو الجدول موجود
        if (Schema::hasTable('support_messages')) {
            return;
        }

        Schema::create('support_messages', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('business_id');
            $t->enum('sender_role', ['business', 'admin']);
            $t->unsignedBigInteger('sender_id');
            $t->unsignedBigInteger('context_user_id')->nullable(); // الهدف من رسالة الأدمن
            $t->text('body');
            $t->timestamp('read_by_admin_at')->nullable();
            $t->timestamp('read_by_business_at')->nullable();
            $t->timestamps();

            $t->index(['business_id', 'created_at']);
            $t->index(['business_id', 'context_user_id'], 'support_messages_biz_ctx_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_messages');
    }
};
