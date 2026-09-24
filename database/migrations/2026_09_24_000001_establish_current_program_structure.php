<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('programs', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->string('code', 12)->primary();
            $table->string('name', 150);
            $table->string('department', 150);
            $table->timestamps();
        });
        foreach ([
            'BLIS' => 'Bachelor of Library and Information Science',
            'BSEnSE' => 'Bachelor of Science in Environmental Science',
            'BSIT' => 'Bachelor of Science in Information Technology',
            'BSCpE' => 'Bachelor of Science in Computer Engineering',
        ] as $code => $name) {
            DB::table('programs')->insert(['code' => $code, 'name' => $name, 'department' => 'School of Information Technology and Engineering', 'created_at' => now(), 'updated_at' => now()]);
        }
        Schema::table('courses', fn (Blueprint $table) => $table->dropUnique(['code', 'department']));
        foreach (['employees', 'courses'] as $name) {
            // Some imported WAMP databases use MyISAM, which silently ignores foreign keys.
            if (DB::connection()->getDriverName() === 'mysql') {
                DB::statement("ALTER TABLE `{$name}` ENGINE=InnoDB");
            }
            Schema::table($name, function (Blueprint $table) {
                $table->string('program', 12)->nullable()->index();
                $table->string('legacy_department', 150)->nullable();
                $table->string('department', 150)->nullable()->change();
                $table->foreign('program')->references('code')->on('programs')->restrictOnDelete();
            });
            DB::table($name)->update(['legacy_department' => DB::raw('department')]);
            // Explicit mappings only; generic Engineering is never guessed to be Computer Engineering.
            foreach ([
                'BSIT' => ['Information Technology', 'IT', 'BSIT', 'Bachelor of Science in Information Technology'],
                'BLIS' => ['BLIS', 'Bachelor of Library and Information Science'],
                'BSEnSE' => ['BSEnSE', 'Bachelor of Science in Environmental Science'],
                'BSCpE' => ['BSCpE', 'Bachelor of Science in Computer Engineering'],
            ] as $program => $legacy) {
                DB::table($name)->whereIn('legacy_department', $legacy)->update(['program' => $program, 'department' => 'School of Information Technology and Engineering']);
            }
        }
        // Course codes may legitimately repeat between programs; keep existing row IDs intact.
        Schema::table('courses', function (Blueprint $table) {
            $table->unique(['code', 'program']);
        });
        foreach (['announcements', 'document_requests'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->string('program', 50)->nullable()->index());
            DB::table($name)->update(['program' => DB::raw('department')]);
            DB::table($name)->where('department', 'Information Technology')->update(['program' => 'BSIT']);
        }
    }

    public function down(): void
    {
        throw new RuntimeException('This migration preserves legacy records and introduces program assignments. Restore a coordinated pre-deployment backup instead of dropping assignment history.');
    }
};
