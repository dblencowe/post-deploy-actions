<?php

namespace Dblencowe\PostDeploy\Console\Commands;

use DB;
use Dblencowe\PostDeploy\PostDeployTrait;
use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use \Illuminate\Foundation\Application;

class PostDeployRunCommand extends Command
{
    use PostDeployTrait;

    /** @var string Command */
    protected $signature = 'postdeploy:run';

    /** @var string Command Description */
    protected $description = 'Run pending post deployment commands';

    /** @var Application Application */
    private $app;

    /** @var Connection Database connection */
    private $schema;

    /**
     * Initialise class, environment & configuration
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->schema = $app['db']->connection()->getSchemaBuilder();

        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function handle()
    {
        $environment = $this->app->environment();

        if (!$this->schema->hasTable('postdeploy_actions')) {
            $this->error('postdeploy_actions table missing. Please run migrations.');
            exit(1);
        }

        // Set the batch number
        $batch = DB::table('postdeploy_actions')->select('batch')->limit(1)->orderBy('batch', 'DESC')->first()->batch + 1;

        $this->install($environment);

        $globalFiles = scandir($this->getActionPath());
        $fileList = array_map(function($value) {
            return $this->getActionPath() . '/' . $value;
        }, $globalFiles);

        $environmentFiles = scandir($this->getActionPath($environment));
        $fileList += array_map(function($value) use($environment) {
            return $this->getActionPath($environment) . '/' . $value;
        }, $environmentFiles);

        foreach ($fileList as $file) {
            if (!is_file($file)) {
                continue;
            }

            $parts = explode(DIRECTORY_SEPARATOR, $file);
            $name = end($parts);

            // Check if this has already been run
            $existing = DB::table('postdeploy_actions')->where([
                'environment' => $environment,
                'action' => $name,
             ])->first();
            if ($existing) {
                continue;
            }

            if (require $file) {
                $this->line("<info>Ran $file</info>");

                // Write the name to the database
                DB::table('postdeploy_actions')->insert([
                    ['environment' => $environment, 'action' => $name, 'batch' => $batch],
                ]);
            }
        }
    }
}
