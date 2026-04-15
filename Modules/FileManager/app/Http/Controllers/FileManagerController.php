<?php

namespace Modules\FileManager\app\Http\Controllers;

use App\Http\Controllers\Controller;

class FileManagerController extends Controller
{
    public function index()
    {
        $mediaDir = storage_path('app/public/media');
        $installFile = $mediaDir . DIRECTORY_SEPARATOR . 'install.php';
        $indexFile = $mediaDir . DIRECTORY_SEPARATOR . 'index.php';

        $state = match (true) {
            file_exists($installFile) => 'install',
            file_exists($indexFile)   => 'ready',
            default                    => 'missing',
        };

        return view('filemanager::index', compact('state'));
    }
}
