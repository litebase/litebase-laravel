<?php

namespace Litebase;

use Illuminate\Console\Command;
use Litebase\QueryProxyServer;

class ServeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'litebase:serve {--port=8100}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start the Litebase proxy server.';

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
     */
    public function handle(LitebaseClient $client): void
    {
        QueryProxyServer::run($client, $this->option('port'));
    }
}
