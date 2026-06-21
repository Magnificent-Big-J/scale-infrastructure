<?php

namespace App\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ActivityFeedServiceInterface
{
    /**
     * Paginate the audit trail, newest first.
     *
     * @param  array{log_name?: ?string, subject_type?: ?string, subject_id?: ?string, causer_id?: ?string}  $filters
     */
    public function paginate(int $perPage, array $filters = []): LengthAwarePaginator;
}
