<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ModuleDemoRecord extends Model
{
    use HasUuids;

    protected $fillable = [
        'page_key',
        'permission',
        'label',
        'headline',
        'summary',
        'status',
        'metrics',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'metrics' => 'array',
            'sort_order' => 'integer',
        ];
    }
}
