<?php

use Illuminate\Support\Facades\File;

test('resource view files have been removed', function () {
    $viewFiles = File::isDirectory(resource_path('views'))
        ? File::allFiles(resource_path('views'))
        : [];

    expect($viewFiles)->toBeEmpty();
});
