<?php

namespace App\Websocket;

use BeyondCode\LaravelWebSockets\Apps\App;
use BeyondCode\LaravelWebSockets\Dashboard\DashboardLogger;
use BeyondCode\LaravelWebSockets\Facades\StatisticsLogger;
use BeyondCode\LaravelWebSockets\QueryParameters;
use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager;
use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManagers\ArrayChannelManager;
use BeyondCode\LaravelWebSockets\WebSockets\Exceptions\ConnectionsOverCapacity;
use BeyondCode\LaravelWebSockets\WebSockets\Exceptions\UnknownAppKey;
use BeyondCode\LaravelWebSockets\WebSockets\Exceptions\WebSocketException;
use BeyondCode\LaravelWebSockets\WebSockets\Messages\PusherMessageFactory;
use Exception;
use Illuminate\Support\Arr;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;
use Ratchet\WebSocket\MessageComponentInterface;
use Illuminate\Support\Facades\Redis;


class AppWebsocketHandler implements MessageComponentInterface
{
    /** @var \BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager */
    protected $channelManager;

    protected $clients;

    public function __construct()
    {
        $this->channelManager = config('websockets.channel_manager') !== null && class_exists(config('websockets.channel_manager'))
            ? app(config('websockets.channel_manager')) : new ArrayChannelManager();
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn)
    {
        try {
            // Store the new connection to send messages to later
            $this->clients->attach($conn);
            // $conn->send('welcome');
            $this
                ->verifyAppKey($conn)
                ->limitConcurrentConnections($conn)
                ->generateSocketId($conn)
                ->establishConnection($conn);
            Log::v('S', $conn, 'welcome');

            if(!empty($conn->httpRequest)) {
                $request = (array)$conn->httpRequest;
                $request = (array)Arr::get($request, "\x00GuzzleHttp\Psr7\Request\x00headers");
                $request = (array)Arr::get($request, "User-Agent");
                $request = Arr::get($request, "0");
            } else {
                $request = 'unknown';
            }

            Log::v('R', $conn, "new client({$conn->resourceId}) on {$conn->remoteAddress}({$request})");
        } catch (Exception $e) {
            Log::e($e);
        }
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $message = AppMessageFactory::createForMessage($msg, $from, $this->channelManager);

        $message->respond();

        //StatisticsLogger::webSocketMessage($from);
        try {
            Log::v('R', $from, "receiving message \"{$msg}\"");
//            $from->send($msg);
            Log::v('S', $from, "sending message \"{$msg}\"");
//            $numRecv = count($this->clients) - 1;
//            foreach ($this->clients as $client) {
//                if ($from !== $client) {
//                    // The sender is not the receiver, send to each client connected
//                    $client->send($msg);
//                    Log::v('S', $client, "sending message \"{$msg}\"");
//                }
//            }
        } catch (Exception $e) {
            Log::e($e);
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->channelManager->removeFromAllChannels($conn);

        try {
            // The connection is closed, remove it, as we can no longer send it messages
            $this->clients->detach($conn);

            Log::v('R', $conn, 'close', "Client({$conn->resourceId}) has disconnected");
        } catch (Exception $e) {
            Log::e($e);
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        Log::e($e);
        $conn->close();
    }

    protected function verifyAppKey(ConnectionInterface $connection)
    {
        $appKey = QueryParameters::create($connection->httpRequest)->get('appKey');

        if (! $app = App::findByKey($appKey)) {
            throw new UnknownAppKey($appKey);
        }

        $connection->app = $app;

        $connection->app->enableClientMessages(true);

        return $this;
    }

    protected function limitConcurrentConnections(ConnectionInterface $connection)
    {
        if (! is_null($capacity = $connection->app->capacity)) {
            $connectionsCount = $this->channelManager->getConnectionCount($connection->app->id);
            if ($connectionsCount >= $capacity) {
                throw new ConnectionsOverCapacity();
            }
        }

        return $this;
    }

    protected function generateSocketId(ConnectionInterface $connection)
    {
        $socketId = sprintf('%d.%d', random_int(1, 1000000000), random_int(1, 1000000000));

        $connection->socketId = $socketId;

        return $this;
    }

    protected function establishConnection(ConnectionInterface $connection)
    {
        $connection->send(json_encode([
            'event' => 'app:connection_established',
            'data' => json_encode([
                'socket_id' => $connection->socketId,
                'activity_timeout' => 30,
            ]),
        ]));

        return $this;
    }
}
