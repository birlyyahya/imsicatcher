<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['nama', 'keterangan'])]
class Satker extends Model
{
    /**
     * Daftar nama satker untuk mengisi opsi select (misi & user).
     *
     * @return array<int, string>
     */
    public static function options(): array
    {
        return static::query()
            ->orderBy('nama')
            ->pluck('nama')
            ->all();
    }
}
