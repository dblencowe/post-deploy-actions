<?php

namespace Dblencowe\PostDeploy\Console\Commands;

use Dblencowe\PostDeploy\PostDeployTrait;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Composer;

class PostDeployMakeCommand extends Command
{
    use PostDeployTrait, ConfirmableTrait;

    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'make:deploy_action {name : The name of the deploy action.} {--env= : Environment}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new post deploy action file';

    /**
     * The migration creator instance.
     *
     * @var \Illuminate\Database\Migrations\MigrationCreator
     */
    protected $creator;

    /**
     * The Composer instance.
     *
     * @var \Illuminate\Support\Composer
     */
    protected $composer;

    /**
     * Create a new migration install command instance.
     *
     * @param  \Illuminate\Support\Composer $composer
     */
    public function __construct(Composer $composer)
    {
        parent::__construct();

        $this->composer = $composer;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        if (!$this->confirmToProceed()) {
            return;
        }

        $name = trim($this->input->getArgument('name'));
        $environment = $this->input->getOption('env') ?? 'global';

        $this->install($environment);

        $this->writeActionFile($name, $environment);

        $this->composer->dumpAutoloads();
    }

    /**
     * Write the action file to disk.
     *
     * @param  string $name
     * @param string $environment
     * @return string
     */
    protected function writeActionFile(string $name, string $environment = 'global')
    {
        $path = $this->getActionPath($environment);
        $fileName = sprintf('%s_%s.php', date('Y_m_d_his'), $name);

        if (!$file = fopen($path . '/' . $fileName, 'wb')) {
            $this->error('Unable to open file ' . $path . '/' . $fileName);
        }

        $content = <<<PHP
<?php

namespace PostDeployAction;

class $name
{
    public function __construct()
    {
        return;
    }
}

(new $name());
PHP;


        fwrite($file, $content);
        fclose($file);

        $this->line("<info>Created Post Deploy Action:</info> $name");
    }
}
