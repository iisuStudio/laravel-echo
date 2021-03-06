<?php
use BeyondCode\LaravelWebSockets\Facades\WebSocketsRouter;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return redirect('index.html');
//    return view('welcome');
});

Route::get('test-broadcast', function(){
    broadcast(new \App\Events\BroadcastEvent(request()->input('m', null)));
    broadcast(new \App\Notifications\Hello);
//    (new \App\Notifications\Hello())->onConnection('redis')->onQueue('default');
});

WebSocketsRouter::webSocket('/my-websocket', \App\MyCustomWebSocketHandler::class);
