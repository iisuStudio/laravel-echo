<?php namespace App\Console\Commands;
use Illuminate\Console\Command;
use React\Socket\ConnectionInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

class Websocket extends Command {
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'websocket:start';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start Websocket Service.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $port = intval($this->option('port'));
        $loop   = \React\EventLoop\Factory::create();

        $webSock = new \React\Socket\Server('0.0.0.0:'.$port, $loop);

        $webServer = new \Ratchet\Server\IoServer(
            new \Ratchet\Http\HttpServer(
                new \Ratchet\WebSocket\WsServer(
                    new \App\Websocket\AppWebsocketHandler()
                )
            ),
            $webSock
        );

        \App\Websocket\Log::v(' ', $loop, "Starting Websocket Service on port " . $port);
        $loop->run();
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['port', 'p', InputOption::VALUE_OPTIONAL, 'Port where to launch the server.', 9090],
        ];
    }
}
