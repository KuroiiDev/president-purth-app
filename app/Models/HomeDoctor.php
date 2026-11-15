<?php

namespace App\Models;

use App\Models\Concerns\BustsHomeCache;
use App\Models\Concerns\HasRevisions;
use Database\Factories\HomeDoctorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HomeDoctor extends Model
{
    /** @use HasFactory<HomeDoctorFactory> */
    use BustsHomeCache, HasFactory, HasRevisions, SoftDeletes;

    protected $table = 'home_doctors';

    protected $fillable = ['image', 'name', 'title', 'text', 'twitter', 'fb', 'linkedin', 'sort_order'];
}
