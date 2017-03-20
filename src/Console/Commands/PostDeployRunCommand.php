<?php

namespace Dblencowe\PostDeploy\Console\Commands;

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
     */
    public function handle()
    {
        $environment = $this->app->environment();

        if (!$this->schema->hasTable('postdeploy_actions')) {
            $this->error('postdeploy_actions table missing. Please run migrations.');
            exit(1);
        }

        // Set the batch number
        $batch = $this->app['db']->table('postdeploy_actions')->select('batch')->limit(1)->orderBy('batch', 'DESC')->first()->batch + 1;

        $this->install($environment);

        $globalFiles = $this->readDirectory($this->getActionPath());
        $environmentFiles = $this->readDirectory($this->getActionPath($environment));
        $fileList = array_merge($globalFiles, $environmentFiles);

        foreach ($fileList as $file) {
            if (!is_file($file)) {
                continue;
            }

            $parts = explode(DIRECTORY_SEPARATOR, $file);
            $name = end($parts);

            // Check if this has already been run
            $exists = $this->app['db']->table('postdeploy_actions')->where([
                'environment' => $environment,
                'action' => $name,
             ])->first();
            if ($exists) {
                continue;
            }

            if (require $file) {
                $this->success($file, $environment, $name, $batch);
            }
        }
    }

    /**
     * Get a list of files in a directory with their full path
     *
     * @param $path
     * @return array
     */
    private function readDirectory($path): array
    {
        $files = scandir($path);

        return array_map(function($value) use($path) {
            return $path . DIRECTORY_SEPARATOR . $value;
        }, $files);
    }

    /**
     * Display a success message and log the run in the DB
     *
     * @param $file
     * @param $environment
     * @param $name
     * @param $batch
     */
    private function success($file, $environment, $name, $batch)
    {
        $this->line("<info>Ran $file</info>");

        // Write the name to the database
        $this->app['db']->table('postdeploy_actions')->insert([
            ['environment' => $environment, 'action' => $name, 'batch' => $batch],
        ]);
    }
}
