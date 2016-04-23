<?php
/*
 +-----------------------------------------------------------------------+
 | Gluu SSO plugin for RoundCube                                         |
 |                                                                       |
 | Copyright (C) 2016 Vlad Karapetyan <vlad.karapetyan.1988@mail.ru>     |
 +-----------------------------------------------------------------------+
 */
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
        $this->add_hook('startup', array($this, 'startup'));
        $this->include_script('gluu_sso.js');
        $this->app = rcmail::get_instance();
        $this->app->output->add_label('Gluu SSO 2.4.2');
        $this->register_action('plugin.gluu_sso', array($this, 'gluu_sso_init'));
        $this->register_action('plugin.gluu_sso-save', array($this, 'gluu_sso_save'));

        $src = $this->app->config->get('skin_path') . '/gluu_sso.css';
        if (file_exists($this->home . '/' . $src)) {
            $this->include_stylesheet($src);
        }
        $this->add_hook('template_object_loginform', array($this,'gluu_sso_loginform'));
    }
    /*
     * Plugin initialization function.
     */
    public function gluu_sso_init()
    {

        $this->register_handler('plugin.body', array($this, 'gluu_sso_form'));

        $this->app->output->set_pagetitle('Gluu SSO 2.4.2');
        $this->app->output->send('plugin');

    }
}
