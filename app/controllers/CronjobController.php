<?php
class CronjobController extends ControllerBase
{
    public function initialize()
    {
        $config = new Phalcon\Config\Adapter\Ini(__DIR__ . '/../config/config.ini');
        $this->config = $config->etsy;
    }
}
