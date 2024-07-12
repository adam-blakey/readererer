<?php

use App\Http\Controllers\PieceController;
use Illuminate\Support\Facades\Route;
use League\Flysystem\StorageAttributes;

Route::get('/', [PieceController::class, 'index']);
Route::get('/pieces',[PieceController::class, 'index']);
Route::get('/pieces/{piece}/edit',[PieceController::class, 'edit']);

Route::get('/test', function() {
    $client = new \Google\Client();
    $client->setClientId(env('GOOGLE_DRIVE_CLIENT_ID'));
    $client->setClientSecret(env('GOOGLE_DRIVE_CLIENT_SECRET'));
    $client->refreshToken(env('GOOGLE_DRIVE_REFRESH_TOKEN'));
    $client->setApplicationName('My Google Drive App');

    $service = new \Google\Service\Drive($client);

//    // variant 1
//    $adapter = new \Masbug\Flysystem\GoogleDriveAdapter($service, 'My_App_Root');
//
//    // variant 2: with extra options and query parameters
//    $adapter2 = new \Masbug\Flysystem\GoogleDriveAdapter(
//        $service,
//        'My_App_Root',
//        [
//            'useDisplayPaths' => true, /* this is the default */
//
//            /* These are global parameters sent to server along with per API parameters. Please see https://cloud.google.com/apis/docs/system-parameters for more info. */
//            'parameters' => [
//                /* This example tells the remote server to perform quota checks per unique user id. Otherwise the quota would be per client IP. */
//                'quotaUser' => (string) 1234
//            ]
//        ]
//    );

//    // variant 3: connect to team drive
//    $adapter3 = new \Masbug\Flysystem\GoogleDriveAdapter(
//        $service,
//        'My_App_Root',
//        [
//            'teamDriveId' => '0GF9IioKDqJsRGk9PVA'
//        ]
//    );

    // variant 4: connect to a folder shared with you
    $adapter4 = new \Masbug\Flysystem\GoogleDriveAdapter(
        $service,
        'My_App_Root',
        [
            'sharedFolderId' => '1C74cCkf0XMHMtZHxOeJl3mUgt5u_iIMr'
        ]
    );

    $fs = new \League\Flysystem\Filesystem($adapter4, [new \League\Flysystem\Config([\League\Flysystem\Config::OPTION_VISIBILITY => \League\Flysystem\Visibility::PRIVATE])]);

    $contents = $fs->listContents('', true)->filter(fn (StorageAttributes $attributes) => $attributes->isFile())->map(fn (StorageAttributes $attributes) => $attributes->path())->toArray();;
    dd($contents);
});
