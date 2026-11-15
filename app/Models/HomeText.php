<?php

namespace App\Models;

use App\Models\Concerns\BustsHomeCache;
use App\Models\Concerns\HasRevisions;
use Database\Factories\HomeTextFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomeText extends Model
{
    /** @use HasFactory<HomeTextFactory> */
    use BustsHomeCache, HasFactory, HasRevisions;

    protected $table = 'home_texts';

    // Karena primary key kita menggunakan string ID (bukan angka 1, 2, 3)
    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'value'];
}
