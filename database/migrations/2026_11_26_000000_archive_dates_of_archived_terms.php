<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** Dates of terms archived before archiving a term archived its dates, or of terms that no longer exist. */
    public function up(): void
    {
        DB::table('term_dates')
            ->whereNull('deleted_at')
            ->whereNotExists(fn ($query) => $query->from('terms')->whereColumn('terms.id', 'term_dates.term_id')->whereNull('terms.deleted_at'))
            ->update(['deleted_at' => DB::raw('coalesce((select terms.deleted_at from terms where terms.id = term_dates.term_id), '.DB::getPdo()->quote(now()->toDateTimeString()).')')]);
    }
};
