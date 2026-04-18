<?php

namespace Modules\Content\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Content\app\Models\Vj;

class VjSeeder extends Seeder
{
    public function run(): void
    {
        $vjs = [
            'VJ Junior',
            'VJ Jingo',
            'VJ Emmy',
            'VJ Mark',
            'VJ Ice P',
            'VJ Kevo',
            'VJ S.M.K',
            'VJ Kyle',
            'VJ Andy',
            'VJ Heavy Q',
        ];

        foreach ($vjs as $name) {
            Vj::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name]
            );
        }
    }
}
