<?php

if (!app()->environment('local')) {
    abort(501);
}

UploadToS3(storage_path('app/media/Images/000000.png'), '/test.png');
