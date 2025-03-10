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
        Schema::table('templates', function (Blueprint $table) {
            $table->enum('status_new', array_column(TemplateStatusEnum::cases(), 'value'))
                ->default(TemplateStatusEnum::Pending->value)
                ->after('status');
        });

        DB::table('templates')->update([
            'status_new' => DB::raw('status'),
        ]);

        Schema::table('templates', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('templates', function (Blueprint $table) {
            $table->renameColumn('status_new', 'status');
        });
    }

    public function down(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->enum('status_old', ['pending', 'completed', 'failed'])
                ->default(TemplateStatusEnum::Pending->value)
                ->after('status');
        });

        DB::table('templates')
            ->whereIn('status', ['pending', 'completed', 'failed'])
            ->update([
                'status_old' => DB::raw('status'),
            ]);

        DB::table('templates')
            ->where('status', 'downloaded')
            ->update([
                'status_old' => 'completed',
            ]);

        Schema::table('templates', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('templates', function (Blueprint $table) {
            $table->renameColumn('status_old', 'status');
        });
    }
};
