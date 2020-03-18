<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DeployPackage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deploy:package';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will use git & composer to archive project to storage/deploy/.';

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
        $result = [];
        exec(base_path() . '/deploy_package.sh', $result);
        foreach ($result as $msg) {
            echo  $msg . PHP_EOL;
        }
    }
}
