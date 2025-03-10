<?php

declare(strict_types=1);

use App\Enums\TemplateStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('templates', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->json('data');
            $table->enum('status', array_column(TemplateStatusEnum::cases(), 'value'))->default(TemplateStatusEnum::Pending);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('templates');
    }
};
