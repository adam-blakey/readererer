<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use SDamian\Larasort\AutoSortable;

class Setlist extends Model
{
    use HasFactory, AutoSortable, SoftDeletes;

    protected $visible = [
        'name',
        'created_at',
        'updated_at',
    ];

    public array $sortables = [
        'name',
        'created_at',
        'updated_at',
    ];

    public function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function pieces()
    {
        return $this->belongsToMany(Piece::class, 'setlist_piece')
            ->withPivot('order');
    }

    public function concerts()
    {
        return $this->belongsToOne(TermDate::class);
    }
}