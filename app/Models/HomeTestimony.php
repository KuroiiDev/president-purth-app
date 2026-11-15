<?php

namespace App\Models;

use App\Models\Concerns\BustsHomeCache;
use App\Models\Concerns\HasRevisions;
use Database\Factories\HomeTestimonyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HomeTestimony extends Model
{
    /** @use HasFactory<HomeTestimonyFactory> */
    use BustsHomeCache, HasFactory, HasRevisions, SoftDeletes;

    protected $table = 'home_testimonies';

    protected $fillable = ['image', 'name', 'title', 'text'];
}
