<?php

/*
 +-----------------------------------------------------------------------+
 | Gluu SSO plugin for RoundCube                                         |
 |                                                                       |
 | Copyright (C) 2016 Vlad Karapetyan <vlad.karapetyan.1988@mail.ru>     |
 +-----------------------------------------------------------------------+
 */

define ('PLUGIN_SUCCESS', 0);
define ('PLUGIN_ERROR_DEFAULT', 1);
define ('PLUGIN_ERROR_CONNECT', 2);
define ('PLUGIN_ERROR_PROCESS', 3);

class gluu_sso extends rcube_plugin
{
    public $task = 'login|logout|settings';
    private $app;
    private $obj;
    private $config;

    /*
     * Initializes the plugin.
     */
    public function init()
    {


    }

}
