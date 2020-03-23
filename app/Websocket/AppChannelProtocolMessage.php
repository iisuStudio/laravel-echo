<?php

namespace App\Websocket;

use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager;
use BeyondCode\LaravelWebSockets\WebSockets\Messages\PusherMessage;
use Illuminate\Support\Str;
use Ratchet\ConnectionInterface;
use stdClass;

class AppChannelProtocolMessage implements PusherMessage
{
    /** @var \stdClass */
    protected $payload;

    /** @var \React\Socket\ConnectionInterface */
    protected $connection;

    /** @var \BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager */
    protected $channelManager;

    public function __construct(stdClass $payload, ConnectionInterface $connection, ChannelManager $channelManager)
    {
        $this->payload = $payload;

        $this->connection = $connection;

        $this->channelManager = $channelManager;
    }

    public function respond()
    {
        $eventName = Str::camel(Str::after($this->payload->event, ':'));

        if (method_exists($this, $eventName) && $eventName !== 'respond') {
            // TODO::event function name in app
            // call_user_func([$this, $eventName], $this->connection, $this->payload->data ?? new stdClass());
        } elseif ($eventName == 'init') {
            $this->registerUID($this->connection, $this->payload);
        }
    }

    protected function registerUID(ConnectionInterface $connection, stdClass $payload)
    {
        $payload->data = $payload->data ?? (object) [];
        $payload->channel = 'UID_' . $payload->uid;
        $this->subscribe($connection, $payload);
    }

    /*
     * @link https://pusher.com/docs/pusher_protocol#ping-pong
     */
    protected function ping(ConnectionInterface $connection)
    {
        $connection->send(json_encode([
            'event' => 'app:pong',
        ]));
    }

    /*
     * @link https://pusher.com/docs/pusher_protocol#pusher-subscribe
     */
    protected function subscribe(ConnectionInterface $connection, stdClass $payload)
    {
        $channel = $this->channelManager->findOrCreate($connection->app->id, $payload->channel);

        $channel->subscribe($connection, $payload);
    }

    public function unsubscribe(ConnectionInterface $connection, stdClass $payload)
    {
        $channel = $this->channelManager->findOrCreate($connection->app->id, $payload->channel);

        $channel->unsubscribe($connection);
    }
}
