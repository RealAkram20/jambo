<?php

namespace Modules\Frontend\app\View\Composers;

use Illuminate\View\View;
use Modules\Content\app\Models\Genre;

class HeaderComposer
{
    private static $cache = null;

    public function compose(View $view): void
    {
        if (self::$cache === null) {
            self::$cache = Genre::orderBy('name')->get(['id', 'name', 'slug']);
        }

        $view->with('headerGenres', self::$cache);
    }
}
