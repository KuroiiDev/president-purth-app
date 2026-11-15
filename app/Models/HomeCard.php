<?php

namespace App\Models;

use App\Models\Concerns\BustsHomeCache;
use App\Models\Concerns\HasRevisions;
use Database\Factories\HomeCardFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HomeCard extends Model
{
    /** @use HasFactory<HomeCardFactory> */
    use BustsHomeCache, HasFactory, HasRevisions, SoftDeletes;

    protected $table = 'home_cards';

    protected $fillable = ['section', 'icon', 'title', 'text', 'sort_order'];
}
