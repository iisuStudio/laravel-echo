<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DeployRsyncDaemon extends Command
{
    /**
     * The interval (in seconds) the scheduler is run daemon mode.
     *
     * @var int
     */
    const SCHEDULE_INTERVAL = 10;

    /**
     * The delay (in seconds) the scheduler is run daemon mode.
     *
     * @var int
     */
    const SCHEDULE_DELAY = 10;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deploy:rsync {--delay= : Number of seconds to delay command} {--interval= : Number of seconds to stop command}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will use rsync from [current] to [target] in daemon mode.';

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
        $start = time();
        $interval = $this->option('interval') ? intval($this->option('interval')) : self::SCHEDULE_INTERVAL;
        $delay = $this->option('delay') ? intval($this->option('delay')) : self::SCHEDULE_DELAY;

        while (1) {
            $result = [];
            exec(base_path() . '/deploy_rsync.sh', $result);
            foreach ($result as $msg) {
                echo  $msg . PHP_EOL;
            }
            echo '----------------------------------------------------------'. PHP_EOL;
            sleep($delay);
            $sleepTime = max(0, $interval - (time() - $start));
            if (0 == $sleepTime) {
                break;
            }
        }
    }
}
