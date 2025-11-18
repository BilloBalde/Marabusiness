<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function realisations()
    {
        return $this->hasMany(Realisation::class);
    }

    // In Project.php
    public function getAverageProgressionAttribute()
    {
        return $this->realizations()->avg('progression') ?? 0;
    }

}
