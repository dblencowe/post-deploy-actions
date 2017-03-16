<?php

namespace Dblencowe\PostDeploy;

use File;

trait PostDeployTrait
{
    /**
     * Get the absolute path to deploy actions
     *
     * @param string $environment
     * @return string
     */
    public function getActionPath(string $environment = 'global')
    {
        return base_path() . '/resources/postdeploy/' . $environment;
    }

    /**
     * @param $environment
     */
    public function install($environment)
    {
        if (!is_dir($this->getActionPath()) && !File::makeDirectory($this->getActionPath(), 0775, true)) {
            $this->error('Unable to create ' . $this->getActionPath());
            exit(1);
        }

        if (!is_dir($this->getActionPath($environment)) && !File::makeDirectory($this->getActionPath($environment), 0775, true)) {
            $this->error('Unable to create ' . $this->getActionPath($environment));
            exit(1);
        }
    }
}
