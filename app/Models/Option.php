<?php

namespace App\Models;

use Database\Factories\OptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'value'])]
class Option extends Model
{
    /** @use HasFactory<OptionFactory> */
    use HasFactory;

    public $timestamps = false;
}
