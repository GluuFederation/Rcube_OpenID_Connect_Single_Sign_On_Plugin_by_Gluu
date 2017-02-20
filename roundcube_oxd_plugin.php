<?php
	
	/**
	 * @copyright Copyright (c) 2017, Gluu Inc. (https://gluu.org/)
	 * @license	  MIT   License            : <http://opensource.org/licenses/MIT>
	 *
	 * @package	  OpenID Connect SSO Plugin by Gluu
	 * @category  Plugin for RoundCube WebMail
	 * @version   3.0.0
	 *
	 * @author    Gluu Inc.          : <https://gluu.org>
	 * @link      Oxd site           : <https://oxd.gluu.org>
	 * @link      Documentation      : <https://oxd.gluu.org/docs/plugin/roundcube/>
	 * @director  Mike Schwartz      : <mike@gluu.org>
	 * @support   Support email      : <support@gluu.org>
	 * @developer Volodya Karapetyan : <https://github.com/karapetyan88> <mr.karapetyan88@gmail.com>
	 *
	 *
	 * This content is released under the MIT License (MIT)
	 *
	 * Copyright (c) 2017, Gluu inc, USA, Austin
	 *
	 * Permission is hereby granted, free of charge, to any person obtaining a copy
	 * of this software and associated documentation files (the "Software"), to deal
	 * in the Software without restriction, including without limitation the rights
	 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
	 * copies of the Software, and to permit persons to whom the Software is
	 * furnished to do so, subject to the following conditions:
	 *
	 * The above copyright notice and this permission notice shall be included in
	 * all copies or substantial portions of the Software.
	 *
	 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
	 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
	 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
	 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
	 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
	 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
	 * THE SOFTWARE.
	 *
	 */

class roundcube_oxd_plugin extends rcube_plugin
{
    public $task = 'login|logout|settings';
    private $app;
    private $obj;
    private $config;
    static $gluuDB;

    public function __construct(rcube_plugin_api $api)
    {
        parent::__construct($api);
        $RCMAIL = rcmail::get_instance($GLOBALS['env']);
        self::$gluuDB = $RCMAIL->db;
    }
    public function init()
    {
        $rcmail = rcmail::get_instance();
        $this->app = rcmail::get_instance();
        $this->load_config();

        $this->add_texts('localization/', false);
        $this->add_hook('startup', array($this, 'startup'));
        $this->include_script('gluu_sso.js');

        $this->app->output->add_label('OpenID Connect Single Sign-On (SSO) Plugin by Gluu');

        $src = $this->app->config->get('skin_path') . '/gluu_sso.css';
        if (file_exists($this->home . '/' . $src)) {
            $this->include_stylesheet($src);
        }

        $this->register_action('plugin.gluu_sso', array($this, 'gluu_sso_init'));
        $this->register_action('plugin.gluu_sso-save', array($this, 'gluu_sso_save'));
        $this->register_action('plugin.gluu_sso-edit', array($this, 'gluu_sso_edit'));
        $this->register_action('plugin.gluu_sso-openidconfig', array($this, 'gluu_sso_openidconfig'));
        $this->add_hook('template_object_loginform', array($this,'gluu_sso_loginform'));
    }
    public function gluu_sso_init()
    {
        $this->register_handler('plugin.body', array($this, 'gluu_sso_form'));
        $this->app->output->set_pagetitle('OpenID Connect Single Sign-On (SSO) Plugin by Gluu');
        $this->app->output->send('plugin');
    }
    public function gluu_sso_edit()
    {
        $this->register_handler('plugin.body', array($this, 'gluu_sso_form_edit'));
        $this->app->output->set_pagetitle('OpenID Connect Single Sign-On (SSO) Plugin by Gluu');
        $this->app->output->send('plugin');
    }
    public function gluu_sso_openidconfig()
    {
        $this->register_handler('plugin.body', array($this, 'gluu_sso_form_openidconfig'));
        $this->app->output->set_pagetitle('OpenID Connect Single Sign-On (SSO) Plugin by Gluu');
        $this->app->output->send('plugin');
    }
    public function admin_html()
    {
        $base_url = $this->getBaseUrl();
        $RCMAIL = rcmail::get_instance($GLOBALS['env']);
        $db = $RCMAIL->db;
        $result = $db->query("CREATE TABLE IF NOT EXISTS `gluu_table` (

              `gluu_action` varchar(255) NOT NULL,
              `gluu_value` longtext NOT NULL,
              UNIQUE(`gluu_action`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
        if (!$this->gluu_db_query_select('gluu_scopes')) {
            $get_scopes = json_encode(array("openid", "profile", "email","imapData"));
            $result = $this->gluu_db_query_insert('gluu_scopes', $get_scopes);
        }
        if (!$this->gluu_db_query_select('gluu_acr')) {
            $custom_scripts = json_encode(array('none'));
            $result = $this->gluu_db_query_insert('gluu_acr', $custom_scripts);
        }
        if (!$this->gluu_db_query_select('gluu_config')) {
            $gluu_config = json_encode(array(
                "gluu_oxd_port" => 8099,
                "admin_email" => $_SESSION['username'],
                "authorization_redirect_uri" => $base_url.'?_action=plugin.gluu_sso-login-from-gluu',
                "post_logout_redirect_uri" => $base_url.'?_task=logout&logout=fromop',
                "config_scopes" => ["openid","profile","email","imapData"],
                "gluu_client_id" => "",
                "gluu_client_secret" => "",
                "config_acr" => []
            ));
            $result = $this->gluu_db_query_insert('gluu_config', $gluu_config);
        }
        if (!$this->gluu_db_query_select('gluu_auth_type')) {
            $gluu_auth_type = 'default';
            $result = $this->gluu_db_query_insert('gluu_auth_type', $gluu_auth_type);
        }
        if (!$this->gluu_db_query_select('gluu_custom_logout')) {
            $gluu_custom_logout = '';
            $result = $this->gluu_db_query_insert('gluu_custom_logout', $gluu_custom_logout);
        }
        if (!$this->gluu_db_query_select('gluu_provider')) {
            $gluu_provider = '';
            $result = $this->gluu_db_query_insert('gluu_provider', $gluu_provider);
        }
        if (!$this->gluu_db_query_select('gluu_send_user_check')) {
            $gluu_send_user_check = 0;
            $result = $this->gluu_db_query_insert('gluu_send_user_check', $gluu_send_user_check);
        }
        if (!$this->gluu_db_query_select('gluu_oxd_id')) {
            $gluu_oxd_id = '';
            $result = $this->gluu_db_query_insert('gluu_oxd_id', $gluu_oxd_id);
        }
        if (!$this->gluu_db_query_select('gluu_user_role')) {
            $gluu_user_role = 0;
            $result = $this->gluu_db_query_insert('gluu_user_role', $gluu_user_role);
        }
        if (!$this->gluu_db_query_select('gluu_users_can_register')) {
            $gluu_users_can_register = 1;
            $result = $this->gluu_db_query_insert('gluu_users_can_register', $gluu_users_can_register);
        }
        if (!$this->gluu_db_query_select('gluu_new_role')) {
            $gluu_users_can_register = 1;
            $result = $this->gluu_db_query_insert('gluu_new_role', null);
        }
        if (!$this->gluu_db_query_select('message_success')) {
            $message_success = '';
            $result = $this->gluu_db_query_insert('message_success', $message_success);
        }
        if (!$this->gluu_db_query_select('message_error')) {
            $message_error = '';
            $result = $this->gluu_db_query_insert('message_error', $message_error);
        }
        if (!$this->gluu_db_query_select('openid_error')) {
            $openid_error = '';
            $result = $this->gluu_db_query_insert('openid_error', $openid_error);
        }
        $get_scopes = json_decode($this->gluu_db_query_select('gluu_scopes'), true);
        $gluu_config = json_decode($this->gluu_db_query_select('gluu_config'), true);
        $gluu_acr = json_decode($this->gluu_db_query_select('gluu_acr'), true);
        $gluu_auth_type = $this->gluu_db_query_select('gluu_auth_type');
        $gluu_send_user_check = $this->gluu_db_query_select('gluu_send_user_check');
        $gluu_provider = $this->gluu_db_query_select('gluu_provider');
        $gluu_user_role = $this->gluu_db_query_select('gluu_user_role');
        $gluu_custom_logout = $this->gluu_db_query_select('gluu_custom_logout');
        $gluu_new_roles = json_decode($this->gluu_db_query_select('gluu_new_role'));
        $gluu_users_can_register = $this->gluu_db_query_select('gluu_users_can_register');
        $message_error = $this->gluu_db_query_select('message_error');
        $message_success = $this->gluu_db_query_select('message_success');
        $openid_error = $this->gluu_db_query_select('openid_error');
        $html='
<link href="plugins/roundcube_oxd_plugin/GluuOxd_Openid/css/gluu-oxd-css.css" rel="stylesheet"/>
<link href="plugins/roundcube_oxd_plugin/GluuOxd_Openid/css/gluu-oxd-css1.css" rel="stylesheet"/>
<link href="plugins/roundcube_oxd_plugin/GluuOxd_Openid/css/bootstrap.css" rel="stylesheet"/>
<script type="application/javascript">
    jQuery(document).ready(function() {

        jQuery(\'[data-toggle="tooltip"]\').tooltip();
        jQuery(\'#p_role\').on(\'click\', \'a.remrole\', function() {
            jQuery(this).parents(\'.role_p\').remove();
        });

    });';
        $html.='jQuery(document ).ready(function() {
        jQuery(document).ready(function() {

            jQuery(\'[data-toggle="tooltip"]\').tooltip();
            jQuery(\'#p_role\').on(\'click\', \'a.remrole\', function() {
                jQuery(this).parents(\'.role_p\').remove();
            });

        });';

        if($gluu_users_can_register == 2){
            $html.='jQuery("#p_role").children().prop(\'disabled\',false);
        jQuery("#p_role *").prop(\'disabled\',false);';
        }
        else{
            $html.='jQuery("#p_role").children().prop(\'disabled\',true);
        jQuery("#p_role *").prop(\'disabled\',true);
        jQuery("input[name=\'gluu_new_role[]\']").each(function(){
            var striped = jQuery(\'#p_role\');
            var value =  jQuery(this).attr("value");
            jQuery(\'<p><input type="hidden" name="gluu_new_role[]"  value= "\'+value+\'"/></p>\').appendTo(striped);
        });';
        }
        $html.='jQuery(\'input:radio[name="gluu_users_can_register"]\').change(function(){
            if(jQuery(this).is(\':checked\') && jQuery(this).val() == \'2\'){
                jQuery("#p_role").children().prop(\'disabled\',false);
                jQuery("#p_role *").prop(\'disabled\',false);
                jQuery("input[type=\'hidden\'][name=\'gluu_new_role[]\']").remove();
                jQuery("#UserType").prop(\'disabled\',false);
            }else if(jQuery(this).is(\':checked\') && jQuery(this).val() == \'3\'){
                jQuery("#p_role").children().prop(\'disabled\',true);
                jQuery("#p_role *").prop(\'disabled\',true);
                jQuery("input[type=\'hidden\'][name=\'gluu_new_role[]\']").remove();
                jQuery("input[name=\'gluu_new_role[]\']").each(function(){
                    var striped = jQuery(\'#p_role\');
                    var value =  jQuery(this).attr("value");
                    jQuery(\'<p><input type="hidden" name="gluu_new_role[]"  value= "\'+value+\'"/></p>\').appendTo(striped);
                });
                jQuery("#UserType").prop(\'disabled\',true);
            }else{
                jQuery("#p_role").children().prop(\'disabled\',true);
                jQuery("#p_role *").prop(\'disabled\',true);
                jQuery("input[type=\'hidden\'][name=\'gluu_new_role[]\']").remove();
                jQuery("input[name=\'gluu_new_role[]\']").each(function(){
                    var striped = jQuery(\'#p_role\');
                    var value =  jQuery(this).attr("value");
                    jQuery(\'<p><input type="hidden" name="gluu_new_role[]"  value= "\'+value+\'"/></p>\').appendTo(striped);
                });
                jQuery("#UserType").prop(\'disabled\',false);
            }
        });
        jQuery("input[name=\'scope[]\']").change(function(){
            var form=$("#scpe_update");
            if (jQuery(this).is(\':checked\')) {
                jQuery.ajax({
                    url: window.location,
                    type: \'POST\',
                    data:form.serialize(),
                    success: function(result){
                        if(result){
                            return false;
                        }
                    }});
            }else{
                jQuery.ajax({
                    url: window.location,
                    type: \'POST\',
                    data:form.serialize(),
                    success: function(result){
                        if(result){
                            return false;
                        }
                    }});
            }
        });
        jQuery(\'#p_role\').on(\'click\', \'.remrole\', function() {
            jQuery(this).parents(\'.role_p\').remove();
        });
    });
</script>';
$html.='
<link href="plugins/roundcube_oxd_plugin/GluuOxd_Openid/css/gluu-oxd-css.css" rel="stylesheet"/>
<script src="plugins/roundcube_oxd_plugin/GluuOxd_Openid/js/scope-custom-script.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
<div class="mo2f_container">
    <div class="container">
        <div id="messages">';
        if (!empty($message_error)) {
            $html .= '<div class="mess_red_error">' . $message_error . '</div>';
            $this->gluu_db_query_update('message_error', '');
        }
        if (!empty($message_success)) {
            $html .= '<div class="mess_green">' . $message_success . '</div>';
            $this->gluu_db_query_update('message_success', '');
        }
        $html .= '</div>
        <ul class="navbar navbar-tabs">
            <li class="active" id="account_setup"><a href="'.$base_url.''.$base_url.'?_task=settings&_action=plugin.gluu_sso">General</a></li>';
        if (!$this->gluu_is_oxd_registered()) {
            $html .= '<li id="social-sharing-setup"><a  style="pointer-events: none; cursor: default;" >OpenID Connect Configuration</a></li>';
        } else {
            $html .= '<li id="social-sharing-setup"><a href="'.$base_url.'?_task=settings&_action=plugin.gluu_sso-openidconfig">OpenID Connect Configuration</a></li>';
        }
        $html .= '<li id=""><a data-method="#configopenid" href="https://oxd.gluu.org/docs/plugin/roundcube/" target="_blank">Documentation</a></li>';
        $html .= '</ul>
        <div class="container-page">';
        if (!$this->gluu_is_oxd_registered()) {

            $html .= '<div class="page" id="accountsetup">
                    <div class="mo2f_table_layout">
                        <form id="register_GluuOxd" name="f" method="post"
                              action="'.$base_url.'?_task=settings&_action=plugin.gluu_sso-save">
                            <input type="hidden" name="form_key" value="general_register_page"/>
                            <div class="login_GluuOxd"> 
                                <br/>
                                <div  style="padding-left: 20px;">Register your site with any standard OpenID Provider (OP). If you need an OpenID Provider you can deploy the <a target="_blank" href="https://gluu.org/docs/deployment/"> free open source Gluu Server.</a></div>
                                <hr>
                                <div style="padding-left: 20px;">This plugin relies on the oxd mediator service. For oxd deployment instructions and license information, please visit the <a target="_blank" href="http://oxd.gluu.org">oxd website.</a></div>
                                <hr>
                                <div style="padding-left: 20px;">
                                    <h3 style=" font-weight:bold;padding-left: 10px;padding-bottom: 20px; border-bottom: 2px solid black; width: 60%; font-weight: bold ">Server Settings</h3>
                                    <table class="table">

                                        <tr>
                                            <td  style="width: 250px"><b>URI of the OpenID Provider:</b></td>
                                            <td><input class="" type="url" name="gluu_provider" id="gluu_provider"
                                                       autofocus="true"  placeholder="Enter URI of the OpenID Provider."
                                                       style="width:400px;"
                                                       value="' . $gluu_provider . '"/>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td  style="width: 250px"><b>Custom URI after logout:</b></td>
                                            <td><input class="" type="url" name="gluu_custom_logout" id="gluu_custom_logout"
                                                       autofocus="true"  placeholder="Enter custom URI after logout"
                                                       style="width:400px;"
                                                       value="' . $gluu_custom_logout . '"/>
                                            </td>
                                        </tr>';
                                        if (!empty($openid_error)) {
                                            $html .= '<tr>
                                                <td  style="width: 250px"><b><font color="#FF0000">*</font>Redirect URL:</b></td>
                                                <td><p>' . $base_url . '?_action=plugin.gluu_sso-login-from-gluu</p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td  style="width: 250px"><b><font color="#FF0000">*</font>Client ID:</b></td>
                                                <td><input class="" type="text" name="gluu_client_id" id="gluu_client_id"
                                                           autofocus="true" placeholder="Enter OpenID Provider client ID."
                                                           style="width:400px;"
                                                           value="';
                if (!empty($gluu_config['gluu_client_id']))
                    $html .= $gluu_config['gluu_client_id'];
                $html .= '"/>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td  style="width: 250px"><b><font color="#FF0000">*</font>Client Secret:</b></td>
                                                <td>
                                                    <input class="" type="text" name="gluu_client_secret" id="gluu_client_secret"
                                                           autofocus="true" placeholder="Enter OpenID Provider client secret."  style="width:400px;" 
                                                           value="';
                if (!empty($gluu_config['gluu_client_secret'])) $html .= $gluu_config['gluu_client_secret'];
                $html .= '"/>
                                                </td>
                                            </tr>';
            }
            $html .= '<tr>
                                            <td  style="width: 250px"><b><font color="#FF0000">*</font>oxd port:</b></td>
                                            <td>
                                                <input class="" type="number" name="gluu_oxd_port" min="0" max="65535"
                                                       value="' . $gluu_config['gluu_oxd_port'] . '"
                                                       style="width:400px;" placeholder="Please enter free port (for example 8099). (Min. number 0, Max. number 65535)."/>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                                <div style="padding-left: 20px">
                                    <h3 style="font-weight:bold;padding-left: 10px;padding-bottom: 20px; border-bottom: 2px solid black; width: 60%;">Enrollment and Access Management
                                        <a data-toggle="tooltip" class="tooltipLink" data-original-title="Choose whether to register new users when they login at an external identity provider. If you disable automatic registration, new users will need to be manually created">
                                            <span class="glyphicon glyphicon-info-sign"></span>
                                        </a>
                                    </h3>
                                    <div class="radio">
                                        <p><label><input name="gluu_users_can_register" type="radio" id="gluu_users_can_register"';
            if ($gluu_users_can_register == 1) {
                $html .= " checked ";
            }
            $html .= 'value="1" style="margin-right: 3px"><b> Automatically login any user with an account in the OpenID Provider</b></label></p>
                                    </div>
                                    <div class="radio">
                                        <p><label ><input name="gluu_users_can_register" type="radio" id="gluu_users_can_register_1"';
            if ($gluu_users_can_register == 2) {
                $html .= " checked ";
            }
            $html .= 'value="2" style="margin-right: 3px"><b> Only register and allow ongoing access to users with one or more of the following roles in the OpenID Provider</b></label></p>
                                        <div style="margin-left: 20px;">
                                            <div id="p_role" >';
            $k = 0;
            if (!empty($gluu_new_roles)) {
                foreach ($gluu_new_roles as $gluu_new_role) {
                    if (!$k) {
                        $k++;
                        $html .= '<p class="role_p" style="padding-top: 10px">
                                                                <input  type="text" name="gluu_new_role[]" required  style="display: inline; width: 200px !important; "
                                                                        placeholder="Input role name"
                                                                        value="' . $gluu_new_role . '"/>
                                                                <button type="button" class="btn btn-xs add_new_role" onclick="test_add()"><span class="glyphicon glyphicon-plus"></span></button>
                                                            </p>';
                    } else {
                        $html .= '<p class="role_p" style="padding-top: 10px">
                                                                <input type="text" name="gluu_new_role[]" required  style="display: inline; width: 200px !important; "
                                                                       placeholder="Input role name"
                                                                       value="' . $gluu_new_role . '"/>
                                                                <button type="button"  class="btn btn-xs add_new_role" onclick="test_add()"><span class="glyphicon glyphicon-plus"></span></button>
                                                                <button type="button"  class="btn btn-xs remrole"><span class="glyphicon glyphicon-minus"></span></button>
                                                            </p>';
                    }
                }
            } else {
                $html .= '<p class="role_p" style="padding-top: 10px">
                                                        <input type="text" name="gluu_new_role[]" required  placeholder="Input role name" style="display: inline; width: 200px !important; " value=""/>
                                                        <button  type="button" class="btn btn-xs add_new_role" onclick="test_add()"><span class="glyphicon glyphicon-plus"></span></button>
                                                    </p>';
            }
            $html .= '</div>
                                        </div>
                                    </div>
                                    <table class="table">';

            if (!empty($openid_error)) {
                $html .= '<tr>
                                                <td style="width: 250px">
                                                    <div><input class="button button-primary button-large" type="submit" name="register" value="Register" style=";width: 120px; float: right;"/></div>
                                                </td>
                                                <td>
                                                    <div><a class="button button-danger button-large" onclick="return confirm(\'Are you sure that you want to remove this OpenID Connect provider? Users will no longer be able to authenticate against this OP.\')" style="padding:2px 5px !important; background-color:red;text-decoration: none;text-align:center; float: left; width: 120px;" href="'.$base_url.'?_task=settings&_action=plugin.gluu_sso-save&submit=delete">Delete</a></div>
                                                </td>
                                            </tr>';
            } else {
                $html .= '<tr>';
                if (!empty($gluu_provider)) {
                    $html .= '<td style="width: 250px">
                                                        <div><input type="submit" name="register" value="Register" style="width: 120px; float: right;" class="button button-primary button-large"/></div>
                                                    </td>
                                                    <td>
                                                        <a class="button button-primary button-large" onclick="return confirm(\'Are you sure that you want to remove this OpenID Connect provider? Users will no longer be able to authenticate against this OP.\')" style="padding:2px 5px !important;background-color:red;text-decoration: none;text-align:center; float: left; width: 120px;" href="'.$base_url.'?_task=settings&_action=plugin.gluu_sso-save&submit=delete">Delete</a>
                                                    </td>';
                } else {
                    $html .= '<td style="width: 250px">
                                                        <div><input type="submit" name="submit" value="Register" style="width: 120px; float: right;" class="button button-primary button-large"/></div>
                                                    </td>
                                                    <td>
                                                    </td>';
                }
                $html .= '</tr>';
            }
            $html .= '</table>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>';
        }
        else {
            $html .= '<div style="padding: 20px !important;" id="accountsetup">
                    <form id="register_GluuOxd" name="f" method="post" action="'.$base_url.'?_task=settings&_action=plugin.gluu_sso-save">
                        <input type="hidden" name="form_key" value="general_oxd_id_reset"/>
                        <fieldset style="border: 2px solid #53cc6b; padding: 20px">
                            <legend style="border-bottom:none; width: 110px !important;">
                                <img style=" height: 45px;" src="plugins/roundcube_oxd_plugin/GluuOxd_Openid/images/icons/gl.png"/>
                            </legend>
                            <div style="padding-left: 20px; margin-top: -30px;">
                                <h3 style="font-weight:bold;padding-left: 10px;padding-bottom: 20px; border-bottom: 2px solid black; width: 60%; font-weight: bold ">Server Settings</h3>
                                <table class="table">
                                    <tr>
                                        <td style="width: 250px"><b>URI of the OpenID Provider:</b></td>
                                        <td><input type="url" name="gluu_provider" id="gluu_provider"
                                                   disabled placeholder="Enter URI of the OpenID Provider."
                                                   style="width:400px;"
                                                   value="' . $gluu_provider . '"/>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="width: 250px"><b>Custom URI after logout:</b></td>
                                        <td><input class="" type="url" name="gluu_custom_logout" id="gluu_custom_logout"
                                                   autofocus="true" disabled placeholder="Enter custom URI after logout"
                                                   style="width:400px;"
                                                   value="' . $gluu_custom_logout . '"/>
                                        </td>
                                    </tr>';
            if (!empty($gluu_config['gluu_client_id']) and !empty($gluu_config['gluu_client_secret'])) {
                $html .= '<tr>
                                            <td style="width: 250px"><b>Redirect URL:</b></td>
                                            <td><p>' . $base_url . '?_action=plugin.gluu_sso-login-from-gluu</p>
                                                </td>
                                        </tr>
                                        <tr>
                                            <td style="width: 250px"><b>Client ID:</b></td>
                                            <td><input class="" type="text" name="gluu_client_id" id="gluu_client_id"
                                                       autofocus="true" placeholder="Enter OpenID Provider client ID."
                                                       style="width:400px; background-color: rgb(235, 235, 228);"
                                                       value="';
                if (!empty($gluu_config['gluu_client_id']))
                    $html .= $gluu_config['gluu_client_id'];
                $html .= '"/>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="width: 250px"><b>Client Secret:</b></td>
                                            <td>
                                                <input class="" type="text" name="gluu_client_secret" id="gluu_client_secret"
                                                autofocus="true" placeholder="Enter OpenID Provider client secret."  style="width:400px; background-color: rgb(235, 235, 228);" value="';
                if (!empty($gluu_config['gluu_client_secret'])) $html .= $gluu_config['gluu_client_secret'];
                $html .= '"/>
                                            </td>
                                        </tr>';
            }
            $html .= '<tr>
                                        <td style="width: 250px"><b>oxd port:</b></td>
                                        <td>
                                            <input class="" type="text" disabled name="gluu_oxd_port" min="0" max="65535"
                                                   value="' . $gluu_config['gluu_oxd_port'] . '"
                                                   style="width:400px; background-color: rgb(235, 235, 228);" placeholder="Please enter free port (for example 8099). (Min. number 0, Max. number 65535)."/>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="width: 250px"><b>oxd ID:</b></td>
                                        <td>
                                            <input class="" type="text" disabled name="oxd_id"
                                                   value="' . $this->gluu_is_oxd_registered() . '"
                                                   style="width:400px;     background-color: rgb(235, 235, 228);" placeholder="Your oxd ID" />
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <div style="padding-left: 20px;">
                                <h3 style="font-weight:bold;padding-left: 10px;padding-bottom: 20px; border-bottom: 2px solid black; width: 60%;">Enrollment and Access Management
                                    <a data-toggle="tooltip" class="tooltipLink" data-original-title="Choose whether to register new users when they login at an external identity provider. If you disable automatic registration, new users will need to be manually created">
                                        <span class="glyphicon glyphicon-info-sign"></span>
                                    </a>
                                </h3>
                                <div>
                                    <p><label><input name="gluu_users_can_register" disabled type="radio" id="gluu_users_can_register"';
            if ($gluu_users_can_register == 1) {
                $html .= ' checked ';
            }
            $html .= 'value="1" style="margin-right: 3px"><b> Automatically login any user with an account in the OpenID Provider</b></label></p>
                                </div>
                                <div>
                                    <p><label ><input name="gluu_users_can_register" disabled type="radio" id="gluu_users_can_register"';
            if ($gluu_users_can_register == 2) {
                $html .= ' checked ';
            }
            $html .= 'value="2" style="margin-right: 3px"> <b>Only register and allow ongoing access to users with one or more of the following roles in the OpenID Provider</b></label></p>
                                    <div style="margin-left: 20px;">
                                        <div id="p_role_disabled">';
            $k = 0;
            if (!empty($gluu_new_roles)) {
                foreach ($gluu_new_roles as $gluu_new_role) {
                    if (!$k) {
                        $k++;
                        $html .= '<p class="role_p" style="padding-top: 10px">
                                                            <input  type="text" name="gluu_new_role[]" disabled  style="display: inline; width: 200px !important; "
                                                                    placeholder="Input role name" 
                                                                    value="'.$gluu_new_role.'"/>
                                                            <button type="button" class="btn btn-xs " disabled="true"><span class="glyphicon glyphicon-plus"></span></button>
                                                        </p>';
                    } else {
                        $html .= '<p class="role_p" style="padding-top: 10px">
                                                            <input type="text" name="gluu_new_role[]" disabled 
                                                                   placeholder="Input role name" style="display: inline; width: 200px !important; "
                                                                   value="'.$gluu_new_role.'"/>
                                                            <button type="button" class="btn btn-xs " disabled="true" ><span class="glyphicon glyphicon-plus"></span></button>
                                                            <button type="button" class="btn btn-xs " disabled="true"><span class="glyphicon glyphicon-minus"></span></button>
                                                        </p>';
                    }
                }
            } else {
                $html .= '<p class="role_p" style="padding-top: 10px">
                                                    <input type="text" name="gluu_new_role[]" disabled placeholder="Input role name" style="display: inline; width: 200px !important; " value=""/>
                                                    <button type="button" class="btn btn-xs " disabled="true" ><span class="glyphicon glyphicon-plus"></span></button>
                                                </p>';
            }
            $html .= '</div>
                                    </div>
                                </div>
                                <table class="table">
                                    <tr>
                                        <td style="width: 250px">
                                            <a class="button button-primary button-large" style="padding:2px 5px !important;text-decoration: none;text-align:center; float: right; width: 120px;background-color: blue" href="'.$base_url.'?_task=settings&_action=plugin.gluu_sso-edit">Edit</a>
                                        </td>
                                        <td>
                                            <input type="submit" onclick="return confirm(\'Are you sure that you want to remove this OpenID Connect provider? Users will no longer be able to authenticate against this OP.\')" name="resetButton" value="Delete" style="background-color:red;text-decoration: none;text-align:center; float: left; width: 120px;" class="button button-danger button-large"/>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </fieldset>
                    </form>
                </div>

            <?php }?>
        </div>
    </div>
</div>';


            return $html;


        }
        return $html;
    }
    public function admin_html_edit()
    {
        $base_url = $this->getBaseUrl();
        $RCMAIL = rcmail::get_instance($GLOBALS['env']);
        $db = $RCMAIL->db;
        $result = $db->query("CREATE TABLE IF NOT EXISTS `gluu_table` (

              `gluu_action` varchar(255) NOT NULL,
              `gluu_value` longtext NOT NULL,
              UNIQUE(`gluu_action`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
        if (!$this->gluu_db_query_select('gluu_scopes')) {
            $get_scopes = json_encode(array("openid", "profile", "email","imapData"));
            $result = $this->gluu_db_query_insert('gluu_scopes', $get_scopes);
        }
        if (!$this->gluu_db_query_select('gluu_acr')) {
            $custom_scripts = json_encode(array('none'));
            $result = $this->gluu_db_query_insert('gluu_acr', $custom_scripts);
        }
        if (!$this->gluu_db_query_select('gluu_config')) {
            $gluu_config = json_encode(array(
                "gluu_oxd_port" => 8099,
                "admin_email" => $_SESSION['username'],
                "authorization_redirect_uri" => $base_url.'?_action=plugin.gluu_sso-login-from-gluu',
                "post_logout_redirect_uri" => $base_url.'?_task=logout&logout=fromop',
                "config_scopes" => ["openid","profile","email","imapData"],
                "gluu_client_id" => "",
                "gluu_client_secret" => "",
                "config_acr" => []
            ));
            $result = $this->gluu_db_query_insert('gluu_config', $gluu_config);
        }
        if (!$this->gluu_db_query_select('gluu_auth_type')) {
            $gluu_auth_type = 'default';
            $result = $this->gluu_db_query_insert('gluu_auth_type', $gluu_auth_type);
        }
        if (!$this->gluu_db_query_select('gluu_custom_logout')) {
            $gluu_custom_logout = '';
            $result = $this->gluu_db_query_insert('gluu_custom_logout', $gluu_custom_logout);
        }
        if (!$this->gluu_db_query_select('gluu_provider')) {
            $gluu_provider = '';
            $result = $this->gluu_db_query_insert('gluu_provider', $gluu_provider);
        }
        if (!$this->gluu_db_query_select('gluu_send_user_check')) {
            $gluu_send_user_check = 0;
            $result = $this->gluu_db_query_insert('gluu_send_user_check', $gluu_send_user_check);
        }
        if (!$this->gluu_db_query_select('gluu_oxd_id')) {
            $gluu_oxd_id = '';
            $result = $this->gluu_db_query_insert('gluu_oxd_id', $gluu_oxd_id);
        }
        if (!$this->gluu_db_query_select('gluu_user_role')) {
            $gluu_user_role = 0;
            $result = $this->gluu_db_query_insert('gluu_user_role', $gluu_user_role);
        }
        if (!$this->gluu_db_query_select('gluu_users_can_register')) {
            $gluu_users_can_register = 1;
            $result = $this->gluu_db_query_insert('gluu_users_can_register', $gluu_users_can_register);
        }
        if (!$this->gluu_db_query_select('gluu_new_role')) {
            $gluu_users_can_register = 1;
            $result = $this->gluu_db_query_insert('gluu_new_role', null);
        }
        if (!$this->gluu_db_query_select('openid_error')) {
            $openid_error = '';
            $result = $this->gluu_db_query_insert('openid_error', $openid_error);
        }
        $get_scopes = json_decode($this->gluu_db_query_select('gluu_scopes'), true);
        $gluu_config = json_decode($this->gluu_db_query_select('gluu_config'), true);
        $gluu_acr = json_decode($this->gluu_db_query_select('gluu_acr'), true);
        $gluu_auth_type = $this->gluu_db_query_select('gluu_auth_type');
        $gluu_send_user_check = $this->gluu_db_query_select('gluu_send_user_check');
        $gluu_provider = $this->gluu_db_query_select('gluu_provider');
        $gluu_user_role = $this->gluu_db_query_select('gluu_user_role');
        $gluu_custom_logout = $this->gluu_db_query_select('gluu_custom_logout');
        $gluu_new_roles = json_decode($this->gluu_db_query_select('gluu_new_role'));
        $gluu_users_can_register = $this->gluu_db_query_select('gluu_users_can_register');
        $message_error = $this->gluu_db_query_select('message_error');
        $message_success = $this->gluu_db_query_select('message_success');
        $openid_error = $this->gluu_db_query_select('openid_error');
        $html='
<link href="plugins/roundcube_oxd_plugin/GluuOxd_Openid/css/gluu-oxd-css.css" rel="stylesheet"/>
<link href="plugins/roundcube_oxd_plugin/GluuOxd_Openid/css/gluu-oxd-css1.css" rel="stylesheet"/>
<link href="plugins/roundcube_oxd_plugin/GluuOxd_Openid/css/bootstrap.css" rel="stylesheet"/>
<script type="application/javascript">
    jQuery(document).ready(function() {

        jQuery(\'[data-toggle="tooltip"]\').tooltip();
        jQuery(\'#p_role\').on(\'click\', \'a.remrole\', function() {
            jQuery(this).parents(\'.role_p\').remove();
        });

    });';
        $html.='jQuery(document ).ready(function() {
        jQuery(document).ready(function() {

            jQuery(\'[data-toggle="tooltip"]\').tooltip();
            jQuery(\'#p_role\').on(\'click\', \'a.remrole\', function() {
                jQuery(this).parents(\'.role_p\').remove();
            });

        });';

        if($gluu_users_can_register == 2){
            $html.='jQuery("#p_role").children().prop(\'disabled\',false);
        jQuery("#p_role *").prop(\'disabled\',false);';
        }
        else{
            $html.='jQuery("#p_role").children().prop(\'disabled\',true);
        jQuery("#p_role *").prop(\'disabled\',true);
        jQuery("input[name=\'gluu_new_role[]\']").each(function(){
            var striped = jQuery(\'#p_role\');
            var value =  jQuery(this).attr("value");
            jQuery(\'<p><input type="hidden" name="gluu_new_role[]"  value= "\'+value+\'"/></p>\').appendTo(striped);
        });';
        }
        $html.='jQuery(\'input:radio[name="gluu_users_can_register"]\').change(function(){
            if(jQuery(this).is(\':checked\') && jQuery(this).val() == \'2\'){
                jQuery("#p_role").children().prop(\'disabled\',false);
                jQuery("#p_role *").prop(\'disabled\',false);
                jQuery("input[type=\'hidden\'][name=\'gluu_new_role[]\']").remove();
                jQuery("#UserType").prop(\'disabled\',false);
            }else if(jQuery(this).is(\':checked\') && jQuery(this).val() == \'3\'){
                jQuery("#p_role").children().prop(\'disabled\',true);
                jQuery("#p_role *").prop(\'disabled\',true);
                jQuery("input[type=\'hidden\'][name=\'gluu_new_role[]\']").remove();
                jQuery("input[name=\'gluu_new_role[]\']").each(function(){
                    var striped = jQuery(\'#p_role\');
                    var value =  jQuery(this).attr("value");
                    jQuery(\'<p><input type="hidden" name="gluu_new_role[]"  value= "\'+value+\'"/></p>\').appendTo(striped);
                });
                jQuery("#UserType").prop(\'disabled\',true);
            }else{
                jQuery("#p_role").children().prop(\'disabled\',true);
                jQuery("#p_role *").prop(\'disabled\',true);
                jQuery("input[type=\'hidden\'][name=\'gluu_new_role[]\']").remove();
                jQuery("input[name=\'gluu_new_role[]\']").each(function(){
                    var striped = jQuery(\'#p_role\');
                    var value =  jQuery(this).attr("value");
                    jQuery(\'<p><input type="hidden" name="gluu_new_role[]"  value= "\'+value+\'"/></p>\').appendTo(striped);
                });
                jQuery("#UserType").prop(\'disabled\',false);
            }
        });
        jQuery("input[name=\'scope[]\']").change(function(){
            var form=$("#scpe_update");
            if (jQuery(this).is(\':checked\')) {
                jQuery.ajax({
                    url: window.location,
                    type: \'POST\',
                    data:form.serialize(),
                    success: function(result){
                        if(result){
                            return false;
                        }
                    }});
            }else{
                jQuery.ajax({
                    url: window.location,
                    type: \'POST\',
                    data:form.serialize(),
                    success: function(result){
                        if(result){
                            return false;
                        }
                    }});
            }
        });
        jQuery(\'#p_role\').on(\'click\', \'.remrole\', function() {
            jQuery(this).parents(\'.role_p\').remove();
        });
    });
</script>';

        $html .= '
<script type="application/javascript">
    var formSubmitting = false;
    var setFormSubmitting = function() { formSubmitting = true; };
    var edit_cancel_function = function() { formSubmitting = true; };
    window.onload = function() {
        window.addEventListener("beforeunload", function (e) {
            if (formSubmitting ) {
                return undefined;
            }

            var confirmationMessage = "You may have unsaved changes. Are you sure you want to leave this page?";

            (e || window.event).returnValue = confirmationMessage; //Gecko + IE
            return confirmationMessage; //Gecko + Webkit, Safari, Chrome etc.
        });
    };
</script>
<link href="plugins/roundcube_oxd_plugin/GluuOxd_Openid/css/gluu-oxd-css.css" rel="stylesheet"/>
<script src="plugins/roundcube_oxd_plugin/GluuOxd_Openid/js/scope-custom-script.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
<div class="mo2f_container">
    <div class="container">
        <div id="messages">';
        if (!empty($message_error)) {
            $html .= '<div class="mess_red_error">' . $message_error . '</div>';
            $this->gluu_db_query_update('message_error', '');
        }
        if (!empty($message_success)) {
            $html .= '<div class="mess_green">' . $message_success . '</div>';
            $this->gluu_db_query_update('message_success', '');
        }
        $html .= '</div>
        <ul class="navbar navbar-tabs">
            <li class="active" id="account_setup"><a href="'.$base_url.'?_task=settings&_action=plugin.gluu_sso">General</a></li>';
        if (!$this->gluu_is_oxd_registered()) {
            $html .= '<li id="social-sharing-setup"><a style="pointer-events: none; cursor: default;" >OpenID Connect Configuration</a></li>';
        }
        else {
            $html .= '<li id="social-sharing-setup"><a href="'.$base_url.'?_task=settings&_action=plugin.gluu_sso-openidconfig">OpenID Connect Configuration</a></li>';
        }
        $html .= '<li id=""><a data-method="#configopenid" href="https://oxd.gluu.org/docs/plugin/roundcube/" target="_blank">Documentation</a></li>';
        $html .= '</ul>
        <div class="container-page">';
        if (!$this->gluu_is_oxd_registered()) {

            $html .= '<div class="page" id="accountsetup">
                    <div class="mo2f_table_layout">
                        <form id="register_GluuOxd" name="f" method="post" action="index.php?module=Gluussos&action=gluuPostData">
                            <input type="hidden" name="form_key" value="general_register_page"/>
                            <div class="login_GluuOxd">
                                <br/>
                                <div  style="padding-left: 20px;">Register your site with any standard OpenID Provider (OP). If you need an OpenID Provider you can deploy the <a target="_blank" href="https://gluu.org/docs/deployment/"> free open source Gluu Server.</a></div>
                                <hr>
                                <div style="padding-left: 20px;">This plugin relies on the oxd mediator service. For oxd deployment instructions and license information, please visit the <a target="_blank" href="http://oxd.gluu.org">oxd website.</a></div>
                                <hr>
                                <div style="padding-left: 20px;">
                                    <h3 style=" font-weight:bold;padding-left: 10px;padding-bottom: 20px; border-bottom: 2px solid black; width: 60%; ">Server Settings</h3>
                                    <table class="table">

                                        <tr>
                                            <td style="width: 250px"><b>URI of the OpenID Provider:</b></td>
                                            <td><input class="" type="url" name="gluu_provider" id="gluu_provider"
                                                       autofocus="true"  placeholder="Enter URI of the OpenID Provider."
                                                       style="width:400px;"
                                                       value="' . $gluu_provider . '"/>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="width: 250px"><b>Custom URI after logout:</b></td>
                                            <td><input class="" type="url" name="gluu_custom_logout" id="gluu_custom_logout"
                                                       autofocus="true"  placeholder="Enter custom URI after logout"
                                                       style="width:400px;"
                                                       value="' . $gluu_custom_logout . '"/>
                                            </td>
                                        </tr>';
            if (!empty($openid_error)) {
                $html .= '<tr>
                                                <td style="width: 250px"><b><font color="#FF0000">*</font>Redirect URL:</b></td>
                                                <td><p>' . $base_url . '?_action=plugin.gluu_sso-login-from-gluu</p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="width: 250px"><b><font color="#FF0000">*</font>Client ID:</b></td>
                                                <td><input class="" type="text" name="gluu_client_id" id="gluu_client_id"
                                                           autofocus="true" placeholder="Enter OpenID Provider client ID."
                                                           style="width:400px;"
                                                           value="';
                if (!empty($gluu_config['gluu_client_id']))
                    $html .= $gluu_config['gluu_client_id'];
                $html .= '"/>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="width: 250px"><b><font color="#FF0000">*</font>Client Secret:</b></td>
                                                <td>
                                                    <input class="" type="text" name="gluu_client_secret" id="gluu_client_secret"
                                                           autofocus="true" placeholder="Enter OpenID Provider client secret."  style="width:400px;" 
                                                           value="';
                if (!empty($gluu_config['gluu_client_secret'])) $html .= $gluu_config['gluu_client_secret'];
                $html .= '"/>
                                                </td>
                                            </tr>';
            }
            $html .= '<tr>
                                            <td style="width: 250px"><b><font color="#FF0000">*</font>oxd port:</b></td>
                                            <td>
                                                <input class="" type="number" name="gluu_oxd_port" min="0" max="65535"
                                                       value="' . $gluu_config['gluu_oxd_port'] . '"
                                                       style="width:400px;" placeholder="Please enter free port (for example 8099). (Min. number 0, Max. number 65535)."/>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                                <div style="padding-left: 20px">
                                    <h3 style="font-weight:bold;padding-left: 10px;padding-bottom: 20px; border-bottom: 2px solid black; width: 60%;">Enrollment and Access Management
                                        <a data-toggle="tooltip" class="tooltipLink" data-original-title="Choose whether to register new users when they login at an external identity provider. If you disable automatic registration, new users will need to be manually created">
                                            <span class="glyphicon glyphicon-info-sign"></span>
                                        </a>
                                    </h3>
                                    <div class="radio">
                                        <p><label><input name="gluu_users_can_register" type="radio" id="gluu_users_can_register"';
            if ($gluu_users_can_register == 1) {
                $html .= " checked ";
            }
            $html .= 'value="1" style="margin-right: 3px"><b> Automatically login any user with an account in the OpenID Provider</b></label></p>
                                    </div>
                                    <div class="radio">
                                        <p><label ><input name="gluu_users_can_register" type="radio" id="gluu_users_can_register_1"';
            if ($gluu_users_can_register == 2) {
                $html .= " checked ";
            }
            $html .= 'value="2" style="margin-right: 3px"><b> Only register and allow ongoing access to users with one or more of the following roles in the OpenID Provider</b></label></p>
                                        <div style="margin-left: 20px;">
                                            <div id="p_role" >';
            $k = 0;
            if (!empty($gluu_new_roles)) {
                foreach ($gluu_new_roles as $gluu_new_role) {
                    if (!$k) {
                        $k++;
                        $html .= '<p class="role_p" style="padding-top: 10px">
                                                                <input  type="text" name="gluu_new_role[]" required  style="display: inline; width: 200px !important; "
                                                                        placeholder="Input role name"
                                                                        value="' . $gluu_new_role . '"/>
                                                                <button type="button" class="btn btn-xs add_new_role" onclick="test_add()"><span class="glyphicon glyphicon-plus"></span></button>
                                                            </p>';
                    } else {
                        $html .= '<p class="role_p" style="padding-top: 10px">
                                                                <input type="text" name="gluu_new_role[]" required  style="display: inline; width: 200px !important; "
                                                                       placeholder="Input role name"
                                                                       value="' . $gluu_new_role . '"/>
                                                                <button type="button"  class="btn btn-xs add_new_role" onclick="test_add()"><span class="glyphicon glyphicon-plus"></span></button>
                                                                <button type="button"  class="btn btn-xs remrole"><span class="glyphicon glyphicon-minus"></span></button>
                                                            </p>';
                    }
                }
            } else {
                $html .= '<p class="role_p" style="padding-top: 10px">
                                                        <input type="text" name="gluu_new_role[]" required  placeholder="Input role name" style="display: inline; width: 200px !important; " value=""/>
                                                        <button  type="button" class="btn btn-xs add_new_role" onclick="test_add()"><span class="glyphicon glyphicon-plus"></span></button>
                                                    </p>';
            }
            $html .= '</div>
                                        </div>
                                    </div>
                                    <table class="table">
                                        ';
            if (!empty($openid_error)) {
                $html .= '<tr>
                                                <td style="width: 250px">
                                                    <div><input class="button button-primary button-large" type="submit" name="register" value="Register" style=";width: 120px; float: right;"/></div>
                                                </td>
                                                <td>
                                                    <div><a class="button button-danger button-large" onclick="return confirm(\'Are you sure that you want to remove this OpenID Connect provider? Users will no longer be able to authenticate against this OP.\')" style="padding:2px 5px !important;background-color:red;text-decoration: none;text-align:center; float: left; width: 120px;" href="'.$base_url.'?_task=settings&_action=plugin.gluu_sso-save&submit=delete">Delete</a></div>
                                                </td>
                                            </tr>';
            } else {
                $html .= '<tr>';
                if (!empty($gluu_provider)) {
                    $html .= '<td style="width: 250px">
                                                        <div><input type="submit" name="register" value="Register" style="width: 120px; float: right;" class="button button-primary button-large"/></div>
                                                    </td>
                                                    <td>
                                                        <a class="button button-primary button-large" onclick="return confirm(\'Are you sure that you want to remove this OpenID Connect provider? Users will no longer be able to authenticate against this OP.\')" style="padding:2px 5px !important;background-color:red;text-decoration: none;text-align:center; float: left; width: 120px;" href="'.$base_url.'?_task=settings&_action=plugin.gluu_sso-save&submit=delete">Delete</a>
                                                    </td>';
                } else {
                    $html .= '<td style="width: 250px">
                                                        <div><input type="submit" name="submit" value="Register" style="width: 120px; float: right;" class="button button-primary button-large"/></div>
                                                    </td>
                                                    <td>
                                                    </td>';
                }
                $html .= '</tr>';
            }
            $html .= '</table>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>';
        } else {
            $html .= '<div style="padding: 20px !important;" id="accountsetup">
<form id="register_GluuOxd" name="f" method="post" onsubmit="setFormSubmitting()"
                              action="'.$base_url.'?_task=settings&_action=plugin.gluu_sso-save">
                    <input type="hidden" name="form_key" value="general_oxd_edit"/>
                    <fieldset style="border: 2px solid #53cc6b; padding: 20px">
                        <legend style="border-bottom:none; width: 110px !important;">
                            <img style=" height: 45px;" src="plugins/roundcube_oxd_plugin/GluuOxd_Openid/images/icons/gl.png"/>
                        </legend>
                        <div style="padding-left: 10px;margin-top: -20px">
                            <h3 style="font-weight:bold;padding-left: 10px;padding-bottom: 20px; border-bottom: 2px solid black; width: 60% ">Server Settings</h3>
                            <table class="table">
                                <tr>
                                    <td style="width: 250px" ><b>URI of the OpenID Connect Provider:</b></td>
                                    <td><input class="" type="url" name="gluu_provider" id="gluu_provider"
                                               autofocus="true" disabled placeholder="Enter URI of the OpenID Connect Provider."
                                               style="width:400px;"
                                               value="'.$gluu_provider.'"/>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="width: 250px" ><b>Custom URI after logout:</b></td>
                                    <td><input class="" type="url" name="gluu_custom_logout" id="gluu_custom_logout"
                                               autofocus="true"  placeholder="Enter custom URI after logout"
                                               style="width:400px;"
                                               value="'.$gluu_custom_logout.'"/>
                                    </td>
                                </tr>';
                                if(!empty($gluu_config['gluu_client_id']) and !empty($gluu_config['gluu_client_secret'])){
                                    $html.='<tr>
                                        <td style="width: 250px" ><b><font color="#FF0000">*</font>Client ID:</b></td>
                                        <td><input class="" type="text" name="gluu_client_id" id="gluu_client_id"
                                                   autofocus="true" placeholder="Enter OpenID Provider client ID."
                                                   style="width:400px; "
                                                   value="';
                                   if(!empty($gluu_config['gluu_client_id'])) $html.=$gluu_config['gluu_client_id'];
                                    $html.='"/>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="width: 250px" ><b><font color="#FF0000">*</font>Client Secret:</b></td>
                                        <td>
                                            <input class="" type="text" name="gluu_client_secret" id="gluu_client_secret"
                                                   autofocus="true" placeholder="Enter OpenID Provider client secret."  style="width:400px; " value="';
                                   if(!empty($gluu_config['gluu_client_secret'])) $html.=$gluu_config['gluu_client_secret'];
                                    $html.='"/>
                                        </td>
                                    </tr>';
                                 }
                                $html.='<tr>
                                    <td style="width: 250px" ><b><font color="#FF0000">*</font>oxd port:</b></td>
                                    <td>
                                        <input class="" type="number"  name="gluu_oxd_port" min="0" max="65535"
                                               value="'.$gluu_config['gluu_oxd_port'].'"
                                               style="width:400px;" placeholder="Please enter free port (for example 8099). (Min. number 0, Max. number 65535)."/>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="width: 250px" ><b>oxd ID:</b></td>
                                    <td>
                                        <input class="" type="text" disabled name="oxd_id"
                                               value="'.$this->gluu_is_oxd_registered().'"
                                               style="width:400px;background-color: rgb(235, 235, 228);" placeholder="Your oxd ID"/>
                                    </td>
                                </tr>
                            </table>
                        </div>
                             <div style="padding-left: 20px">
                                    <h3 style="font-weight:bold;padding-left: 10px;padding-bottom: 20px; border-bottom: 2px solid black; width: 60%;">Enrollment and Access Management
                                        <a data-toggle="tooltip" class="tooltipLink" data-original-title="Choose whether to register new users when they login at an external identity provider. If you disable automatic registration, new users will need to be manually created">
                                            <span class="glyphicon glyphicon-info-sign"></span>
                                        </a>
                                    </h3>
                                    <div class="radio">
                                        <p><label><input name="gluu_users_can_register" type="radio" id="gluu_users_can_register"';
            if ($gluu_users_can_register == 1) {
                $html .= " checked ";
            }
            $html .= 'value="1" style="margin-right: 3px"><b> Automatically login any user with an account in the OpenID Provider</b></label></p>
                                    </div>
                                    <div class="radio">
                                        <p><label ><input name="gluu_users_can_register" type="radio" id="gluu_users_can_register_1"';
            if ($gluu_users_can_register == 2) {
                $html .= " checked ";
            }
            $html .= 'value="2" style="margin-right: 3px"><b> Only register and allow ongoing access to users with one or more of the following roles in the OpenID Provider</b></label></p>
                                        <div style="margin-left: 20px;">
                                            <div id="p_role" >';
            $k = 0;
            if (!empty($gluu_new_roles)) {
                foreach ($gluu_new_roles as $gluu_new_role) {
                    if (!$k) {
                        $k++;
                        $html .= '<p class="role_p" style="padding-top: 10px">
                                                                <input  type="text" name="gluu_new_role[]" required  style="display: inline; width: 200px !important; "
                                                                        placeholder="Input role name"
                                                                        value="' . $gluu_new_role . '"/>
                                                                <button type="button" class="btn btn-xs add_new_role" onclick="test_add()"><span class="glyphicon glyphicon-plus"></span></button>
                                                            </p>';
                    } else {
                        $html .= '<p class="role_p" style="padding-top: 10px">
                                                                <input type="text" name="gluu_new_role[]" required  style="display: inline; width: 200px !important; "
                                                                       placeholder="Input role name"
                                                                       value="' . $gluu_new_role . '"/>
                                                                <button type="button"  class="btn btn-xs add_new_role" onclick="test_add()"><span class="glyphicon glyphicon-plus"></span></button>
                                                                <button type="button"  class="btn btn-xs remrole"><span class="glyphicon glyphicon-minus"></span></button>
                                                            </p>';
                    }
                }
            } else {
                $html .= '<p class="role_p" style="padding-top: 10px">
                                                        <input type="text" name="gluu_new_role[]" required  placeholder="Input role name" style="display: inline; width: 200px !important; " value=""/>
                                                        <button  type="button" class="btn btn-xs add_new_role" onclick="test_add()"><span class="glyphicon glyphicon-plus"></span></button>
                                                    </p>';
            }
            $html .= '</div>
                                        </div>
                                    </div>
                                    <table class="table">
                                <tr>
                                    <td style="width: 250px">
                                        <input type="submit" name="saveButton" value="Save" style="text-decoration: none;text-align:center; float: right; width: 120px;" class="button button-primary button-large"/>
                                    </td>
                                    <td>
                                        <a class="button button-primary button-large" onclick="edit_cancel_function()" style="padding:2px 5px !important;background-color:red;text-align:center;float: left; width: 120px;" href="'.$base_url.'?_task=settings&_action=plugin.gluu_sso">Cancel</a>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </fieldset>
                </form>
                </div>

            <?php }?>
        </div>
    </div>
</div>';


            return $html;


        }
        return $html;
    }
    public function admin_html_openidconfig()
    {
        $base_url = $this->getBaseUrl();
        $RCMAIL = rcmail::get_instance($GLOBALS['env']);
        $db = $RCMAIL->db;
        $result = $db->query("CREATE TABLE IF NOT EXISTS `gluu_table` (

              `gluu_action` varchar(255) NOT NULL,
              `gluu_value` longtext NOT NULL,
              UNIQUE(`gluu_action`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
        if (!$this->gluu_db_query_select('gluu_scopes')) {
            $get_scopes = json_encode(array("openid", "profile", "email","imapData"));
            $result = $this->gluu_db_query_insert('gluu_scopes', $get_scopes);
        }
        if (!$this->gluu_db_query_select('gluu_acr')) {
            $custom_scripts = json_encode(array('none'));
            $result = $this->gluu_db_query_insert('gluu_acr', $custom_scripts);
        }
        if (!$this->gluu_db_query_select('gluu_config')) {
            $gluu_config = json_encode(array(
                "gluu_oxd_port" => 8099,
                "admin_email" => $_SESSION['username'],
                "authorization_redirect_uri" => $base_url.'?_action=plugin.gluu_sso-login-from-gluu',
                "post_logout_redirect_uri" => $base_url.'?_task=logout&logout=fromop',
                "config_scopes" => ["openid","profile","email","imapData"],
                "gluu_client_id" => "",
                "gluu_client_secret" => "",
                "config_acr" => []
            ));
            $result = $this->gluu_db_query_insert('gluu_config', $gluu_config);
        }
        if (!$this->gluu_db_query_select('gluu_auth_type')) {
            $gluu_auth_type = 'default';
            $result = $this->gluu_db_query_insert('gluu_auth_type', $gluu_auth_type);
        }
        if (!$this->gluu_db_query_select('gluu_custom_logout')) {
            $gluu_custom_logout = '';
            $result = $this->gluu_db_query_insert('gluu_custom_logout', $gluu_custom_logout);
        }
        if (!$this->gluu_db_query_select('gluu_provider')) {
            $gluu_provider = '';
            $result = $this->gluu_db_query_insert('gluu_provider', $gluu_provider);
        }
        if (!$this->gluu_db_query_select('gluu_send_user_check')) {
            $gluu_send_user_check = 0;
            $result = $this->gluu_db_query_insert('gluu_send_user_check', $gluu_send_user_check);
        }
        if (!$this->gluu_db_query_select('gluu_oxd_id')) {
            $gluu_oxd_id = '';
            $result = $this->gluu_db_query_insert('gluu_oxd_id', $gluu_oxd_id);
        }
        if (!$this->gluu_db_query_select('gluu_user_role')) {
            $gluu_user_role = 0;
            $result = $this->gluu_db_query_insert('gluu_user_role', $gluu_user_role);
        }
        if (!$this->gluu_db_query_select('gluu_users_can_register')) {
            $gluu_users_can_register = 1;
            $result = $this->gluu_db_query_insert('gluu_users_can_register', $gluu_users_can_register);
        }
        if (!$this->gluu_db_query_select('gluu_new_role')) {
            $gluu_users_can_register = 1;
            $result = $this->gluu_db_query_insert('gluu_new_role', null);
        }
        $get_scopes = json_decode($this->gluu_db_query_select('gluu_scopes'), true);
        $gluu_config = json_decode($this->gluu_db_query_select('gluu_config'), true);
        $gluu_acr = json_decode($this->gluu_db_query_select('gluu_acr'), true);
        $gluu_auth_type = $this->gluu_db_query_select('gluu_auth_type');
        $gluu_send_user_check = $this->gluu_db_query_select('gluu_send_user_check');
        $gluu_provider = $this->gluu_db_query_select('gluu_provider');
        $gluu_user_role = $this->gluu_db_query_select('gluu_user_role');
        $gluu_custom_logout = $this->gluu_db_query_select('gluu_custom_logout');
        $gluu_new_roles = json_decode($this->gluu_db_query_select('gluu_new_role'));
        $gluu_users_can_register = $this->gluu_db_query_select('gluu_users_can_register');
        $message_error = $this->gluu_db_query_select('message_error');
        $message_success = $this->gluu_db_query_select('message_success');
        $html='
<link href="plugins/roundcube_oxd_plugin/GluuOxd_Openid/css/gluu-oxd-css.css" rel="stylesheet"/>
<link href="plugins/roundcube_oxd_plugin/GluuOxd_Openid/css/gluu-oxd-css1.css" rel="stylesheet"/>
<link href="plugins/roundcube_oxd_plugin/GluuOxd_Openid/css/bootstrap.css" rel="stylesheet"/>

';
        $html.="
<script type='application/javascript'>
    jQuery(document).ready(function() {

        jQuery('[data-toggle=\"tooltip\"]').tooltip();
        jQuery('#p_role').on('click', 'a.remrole', function() {
            jQuery(this).parents('.role_p').remove();
        });

    });
    jQuery(document ).ready(function() {

        jQuery(\"input[name='scope[]']\").change(function(){
            var form=$(\"#scpe_update\");
            if (jQuery(this).is(':checked')) {
                jQuery.ajax({
                    url: '?_task=settings&_action=plugin.gluu_sso-save',
                    type: 'POST',
                    data:form.serialize(),
                    success: function(result){
                        if(result){
                            return false;
                        }
                    }});
            }else{
                jQuery.ajax({
                    url: '?_task=settings&_action=plugin.gluu_sso-save',
                    type: 'POST',
                    data:form.serialize(),
                    success: function(result){
                        if(result){
                            return false;
                        }
                    }});
            }
        });

    });
    function delete_scopes(val){
        if (confirm(\"Are you sure that you want to delete this scope? You will no longer be able to request this user information from the OP.\")) {
        var _token = jQuery(\"input[name='_token']\").val();
            jQuery.ajax({
                url: '?_task=settings&_action=plugin.gluu_sso-save',
                type: 'POST',
                data:{form_key_scope_delete:'form_key_scope_delete', delete_scope:val,_token:_token},
                success: function(result){
                    location.reload();
                }});
        }
        else{
            return false;
        }

    }

    function add_scope_for_delete() {
        var striped = jQuery('#table-striped');
        var k = jQuery('#p_scents p').size() + 1;
        var new_scope_field = jQuery('#new_scope_field').val();
        var _token = jQuery(\"input[name='_token']\").val();
        var m = true;
        if(new_scope_field){
            jQuery(\"input[name='scope[]']\").each(function(){
                // get name of input
                var value =  jQuery(this).attr(\"value\");
                if(value == new_scope_field){
                    m = false;
                }
            });
            if(m){
                jQuery('<tr >' +
                    '<td style=\"padding: 0px !important;\">' +
                    '   <p  id=\"'+new_scope_field+'\">' +
                    '     <input type=\"checkbox\" name=\"scope[]\" id=\"new_'+new_scope_field+'\" value=\"'+new_scope_field+'\"  />'+
                    '   </p>' +
                    '</td>' +
                    '<td style=\"padding: 0px !important;\">' +
                    '   <p  id=\"'+new_scope_field+'\">' +
                    new_scope_field+
                    '   </p>' +
                    '</td>' +
                    '<td style=\"padding: 0px !important; \">' +
                    '   <a href=\"#scop_section\" class=\"btn btn-danger btn-xs\" style=\"margin: 5px; float: right\" onclick=\"delete_scopes(\''+new_scope_field+'\')\" >' +
                    '<span class=\"glyphicon glyphicon-trash\"></span>' +
                    '</a>' +
                    '</td>' +
                    '</tr>').appendTo(striped);
                jQuery('#new_scope_field').val('');

                jQuery.ajax({
                    url: '?_task=settings&_action=plugin.gluu_sso-save',
                    type: 'POST',
                    data:{form_key_scope:'oxd_openid_config_new_scope', new_value_scope:new_scope_field,_token:_token},
                    success: function(result){
                        if(result){
                            return false;
                        }
                    }});
                jQuery(\"#new_\"+new_scope_field).change(
                    function(){
                        var form=$(\"#scpe_update\");
                        if (jQuery(this).is(':checked')) {
                            jQuery.ajax({
                                url: '?_task=settings&_action=plugin.gluu_sso-save',
                                type: 'POST',
                                data:form.serialize(),
                                success: function(result){
                                    if(result){
                                        return false;
                                    }
                                }});
                        }else{
                            jQuery.ajax({
                                url: '?_task=settings&_action=plugin.gluu_sso-save',
                                type: 'POST',
                                data:form.serialize(),
                                success: function(result){
                                    if(result){
                                        return false;
                                    }
                                }});
                        }
                    });

                return false;
            }
            else{
                alert('The scope named '+new_scope_field+' is exist!');
                jQuery('#new_scope_field').val('');
                return false;
            }
        }else{
            alert('Please input scope name!');
            jQuery('#new_scope_field').val('');
            return false;
        }
    }
</script>";
        $html.=
            '<link href="plugins/roundcube_oxd_plugin/GluuOxd_Openid/css/gluu-oxd-css.css" rel="stylesheet"/>
<script src="plugins/roundcube_oxd_plugin/GluuOxd_Openid/js/scope-custom-script.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
<div class="mo2f_container">
    <div class="container">
        <div id="messages">';
        if (!empty($message_error)) {
            $html .= '<div class="mess_red_error">' . $message_error . '</div>';
            $this->gluu_db_query_update('message_error', '');
        }
        if (!empty($message_success)) {
            $html .= '<div class="mess_green">' . $message_success . '</div>';
            $this->gluu_db_query_update('message_success', '');
        }
        $html .= '</div>
        <ul class="navbar navbar-tabs">
            <li  id="account_setup"><a href="'.$base_url.'?_task=settings&_action=plugin.gluu_sso">General</a></li>';
        if (!$this->gluu_is_oxd_registered()) {
            $html .= '<li class="active" id="social-sharing-setup"><a style="pointer-events: none; cursor: default;" >OpenID Connect Configuration</a></li>';
        } else {
            $html .= '<li class="active" id="social-sharing-setup"><a href="'.$base_url.'?_task=settings&_action=plugin.gluu_sso-openidconfig">OpenID Connect Configuration</a></li>';
        }
        $html .= '<li id=""><a data-method="#configopenid" href="https://oxd.gluu.org/docs/plugin/roundcube/" target="_blank">Documentation</a></li>';
        $html .= '</ul>
        <div class="container-page">';
        $html .= '            
            <div id="configopenid" style="padding: 20px !important;">
                <form action="'.$base_url.'?_task=settings&_action=plugin.gluu_sso-save" method="post" id="scpe_update">
                    <input type="hidden" name="form_key" value="openid_config_page"/>
                    <fieldset style="border: 2px solid #53cc6b; padding: 20px">
                        <legend style="border-bottom:none; width: 110px !important;">
                            <img style=" height: 45px;" src="plugins/roundcube_oxd_plugin/GluuOxd_Openid/images/icons/gl.png"/>
                        </legend>
                        <h3 style="font-weight:bold;padding-left: 10px;padding-bottom: 20px; border-bottom: 2px solid black; width: 60%; ">User Scopes</h3>
                        <div >
                            <table style="margin-left: 30px" class="form-table">
                                <tr style="border-bottom: 1px solid green !important;">
                                    <th style="width: 230px; padding: 0px">
                                        <h4 style="text-align: left;" id="scop_section">
                                            Requested scopes
                                            <a data-toggle="tooltip" class="tooltipLink" data-original-title="Scopes are bundles of attributes that the OP stores about each user. It is recommended that you request the minimum set of scopes required">
                                                <span class="glyphicon glyphicon-info-sign"></span>
                                            </a>
                                        </h4>
                                    </th>
                                    <td style="width: 230px; padding-left: 10px !important">
                                            <table id="table-striped" class="form-table" >
                                                <tbody style="width: inherit !important;">
                                                <tr style="padding: 0px">
                                                    <td style="padding: 0px !important; width: 10%">
                                                        <p >
                                                            <input checked type="checkbox" name=""  id="openid" value="openid"  disabled />

                                                        </p>
                                                    </td>
                                                    <td style="padding: 0px !important; width: 75%">
                                                        <p >
                                                            <input type="hidden"  name="scope[]"  value="openid" />openid
                                                        </p>
                                                    </td>
                                                    <td style="padding: 0px !important;  width: 20%">
                                                        <a class="btn btn-danger btn-xs" style="margin: 5px; float: right" disabled><span class="glyphicon glyphicon-trash"></span></a>
                                                    </td>
                                                </tr>
                                                <tr style="padding: 0px">
                                                    <td style="padding: 0px !important; width: 10%">
                                                        <p >
                                                            <input checked type="checkbox" name=""  id="email" value="email"  disabled />

                                                        </p>
                                                    </td>
                                                    <td style="padding: 0px !important; width: 70%">
                                                        <p >
                                                            <input type="hidden"  name="scope[]"  value="email" />email
                                                        </p>
                                                    </td>
                                                    <td style="padding: 0px !important;  width: 20%">
                                                        <a class="btn btn-danger btn-xs" style="margin: 5px; float: right" disabled><span class="glyphicon glyphicon-trash"></span></a>
                                                    </td>
                                                </tr>
                                                <tr style="padding: 0px">
                                                    <td style="padding: 0px !important; width: 10%">
                                                        <p >
                                                            <input checked type="checkbox" name=""  id="profile" value="profile"  disabled />

                                                        </p>
                                                    </td>
                                                    <td style="padding: 0px !important; width: 70%">
                                                        <p >
                                                            <input type="hidden"  name="scope[]"  value="profile" />profile
                                                        </p>
                                                    </td>
                                                    <td style="padding: 0px !important;  width: 20%">
                                                        <a class="btn btn-danger btn-xs" style="margin: 5px; float: right" disabled><span class="glyphicon glyphicon-trash"></span></a>
                                                    </td>
                                                </tr>
                                                <tr style="padding: 0px">
                                                    <td style="padding: 0px !important; width: 10%">
                                                        <p >
                                                            <input checked type="checkbox" name=""  id="imapData" value="imapData"  disabled />

                                                        </p>
                                                    </td>
                                                    <td style="padding: 0px !important; width: 70%">
                                                        <p >
                                                            <input type="hidden"  name="scope[]"  value="imapData" />imapData
                                                        </p>
                                                    </td>
                                                    <td style="padding: 0px !important;  width: 20%">
                                                        <a class="btn btn-danger btn-xs" style="margin: 5px; float: right" disabled><span class="glyphicon glyphicon-trash"></span></a>
                                                    </td>
                                                </tr>';
                                                foreach($get_scopes as $scop) {
                                                    if ($scop == 'openid' or $scop == 'email' or $scop == 'profile' or $scop == 'imapData'){
                                                    } else{
                                                        $html.='<tr style="padding: 0px">
                                                            <td>
                                                                <p id="'.$scop.'1">
                                                                    <input ';
                                                        if($gluu_config && in_array($scop, $gluu_config['config_scopes'])){ $html.=" checked ";
                                                        } $html.=' type="checkbox" name="scope[]"  id="'.$scop.'1" value="'.$scop.'" ';
                                                        if (!$this->gluu_is_oxd_registered() || $scop=='openid') $html.=' disabled ';
                                                        $html.='/>
                                                                </p>
                                                            </td>
                                                            <td>
                                                                <p id="'.$scop.'1">'.$scop.'
                                                                </p>
                                                            </td>
                                                            <td style="padding: 0px !important; ">
                                                                <button type="button" class="btn btn-danger btn-xs" style="margin: 5px; float: right" onclick="delete_scopes(\''.$scop.'\')" ><span class="glyphicon glyphicon-trash"></span></button>
                                                            </td>
                                                        </tr>';
                                                     }
                                                }
                                        $html.='</tbody>
                                            </table>
                                    </td>
                                </tr>
                                <tr style="border-bottom: 1px solid green !important;padding: 10px 15px">
                                    <th style="border-bottom: 1px solid green !important;padding: 10px 15px">
                                        <h4 style="text-align: left;" id="scop_section1">
                                            Add scopes
                                        </h4>
                                    </th>
                                    <td style="border-bottom: 1px solid green !important;padding: 10px 15px"> 
                                        <div style="margin-left: 10px" id="p_scents">
                                            <p>
                                                <input ';
                                            if(!$this->gluu_is_oxd_registered()) $html.=' disabled ';
                                            $html.='class="form-control" style="
    height: 25px;" type="text" id="new_scope_field" name="new_scope[]" placeholder="Input scope name" />
                                            </p>
                                            <br/>
                                            <p>
                                                <input type="button" style="width: 80px" class="btn btn-primary btn-large" onclick="add_scope_for_delete()" value="Add" id="add_new_scope"/>
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <br/>
                        <h3 style="font-weight:bold;padding-left: 10px;padding-bottom: 20px; border-bottom: 2px solid black; width: 60%; font-weight: bold ">Authentication</h3>
                        <br/>
                        <p style=" margin-left: 20px; font-weight:bold "><label style="display: inline !important; "><input type="checkbox" name="send_user_check" id="send_user" value="1" ';
                        if(!$this->gluu_is_oxd_registered()) $html.=' disabled ';
                        if( $gluu_send_user_check) $html.=' checked ';
                        $html.= '/> <span>Bypass the local Roundcube login page and send users straight to the OP for authentication</span></label>
                        </p>
                        <br/>
                        <div>
                            <table style="margin-left: 30px" class="form-table">
                                <tbody>
                                <tr>
                                    <th style="width: 200px; padding: 0px; ">
                                        <h4 style="text-align: left;" id="scop_section">
                                            Select ACR: <a data-toggle="tooltip" class="tooltipLink" data-original-title="The OpenID Provider may make available multiple authentication mechanisms. To signal which type of authentication should be used for access to this site you can request a specific ACR. To accept the OP\'s default authentication, set this field to none.">
                                                <span class="glyphicon glyphicon-info-sign"></span>
                                            </a>
                                        </h4>
                                    </th>
                                    <td >';
                                        $custom_scripts = $gluu_acr;
                                        if(!empty($custom_scripts)){
                                            $html.='<select style="margin-left: 5px; padding: 0px !important;" class="form-control" name="send_user_type" id="send_user_type" ';
                                            if(!$this->gluu_is_oxd_registered()) $html.= ' disabled ';
                                            $html.='>
                                                <option value="default">none</option>';
                                                if($custom_scripts){
                                                    foreach($custom_scripts as $custom_script){
                                                        if($custom_script != "default" and $custom_script != "none"){
                                                            $html.='<option ';
                                                            if($gluu_auth_type == $custom_script) $html.='selected';
                                                            $html.=' value="'.$custom_script.'">'.$custom_script.'</option>';

                                                        }
                                                    }
                                                }
                                            $html.='</select>';
                                        }
                            $html.='</td>
                                </tr>
                                <tr>
                                    <th >
                                        <input type="submit" class="btn btn-primary btn-large" ';
                                        if(!$this->gluu_is_oxd_registered()) $html.=' disabled ';
                                        $html.=' value="Save Authentication Settings" name="set_oxd_config" />
                                    </th>
                                    <td>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </fieldset>
                </form>
            </div>';
        return $html;
    }
    public function gluu_sso_form()
    {
        // add taskbar button

        $boxTitle = html::div(array('id' => "prefs-title", 'class' => 'boxtitle'), 'OpenID Connect Single Sign-On (SSO) Plugin by Gluu');

        $tableHtml=$this->admin_html();
        return html::div(array('class' => ''),$boxTitle . html::div(array('class' => "boxcontent"), $tableHtml ));
    }
    public function gluu_sso_form_edit()
    {
        // add taskbar button

        $boxTitle = html::div(array('id' => "prefs-title", 'class' => 'boxtitle'), 'OpenID Connect Single Sign-On (SSO) Plugin by Gluu');

        $tableHtml=$this->admin_html_edit();
        return html::div(array('class' => ''),$boxTitle . html::div(array('class' => "boxcontent"), $tableHtml ));
    }
    public function gluu_sso_form_openidconfig()
    {
        // add taskbar button

        $boxTitle = html::div(array('id' => "prefs-title", 'class' => 'boxtitle'), 'OpenID Connect Single Sign-On (SSO) Plugin by Gluu');

        $tableHtml=$this->admin_html_openidconfig();
        return html::div(array('class' => ''),$boxTitle . html::div(array('class' => "boxcontent"), $tableHtml ));
    }
    public function gluu_sso_save()
    {
        require_once("GluuOxd_Openid/oxd-rp/Register_site.php");
        require_once("GluuOxd_Openid/oxd-rp/Update_site_registration.php");
        $base_url = $this->getBaseUrl();
        $RCMAIL = rcmail::get_instance($GLOBALS['env']);

        $db = $RCMAIL->db;
        if(!empty($_SESSION['username'])){
            if( isset( $_REQUEST['form_key'] ) and strpos( $_REQUEST['form_key'], 'general_register_page' ) !== false ) {
                if(!isset($_SERVER['HTTPS']) or $_SERVER['HTTPS'] != "on") {
                    $this->gluu_db_query_update('message_error', 'OpenID Connect requires https. This plugin will not work if your website uses http only.');
                    header('Location: '.$base_url.'?_task=settings&_action=plugin.gluu_sso');return;
                }
                if($_POST['gluu_users_can_register']==1){
                    $this->gluu_db_query_update('gluu_users_can_register', $_POST['gluu_users_can_register']);
                    if(!empty(array_values(array_filter($_POST['gluu_new_role'])))){
                        $this->gluu_db_query_update('gluu_new_role', json_encode(array_values(array_filter($_POST['gluu_new_role']))));
                        $config = json_decode($this->gluu_db_query_select('gluu_config'),true);
                        array_push($config['config_scopes'],'permission');
                        $gluu_config = json_decode($this->gluu_db_query_update('gluu_config', json_encode($config)),true);
                    }else{
                        $this->gluu_db_query_update('gluu_new_role', json_encode(null));
                    }
                }
                if($_POST['gluu_users_can_register']==2){
                    $this->gluu_db_query_update('gluu_users_can_register', 2);

                    if(!empty(array_values(array_filter($_POST['gluu_new_role'])))){
                        $this->gluu_db_query_update('gluu_new_role', json_encode(array_values(array_filter($_POST['gluu_new_role']))));
                        $config = json_decode($this->gluu_db_query_select('gluu_config'),true);
                        array_push($config['config_scopes'],'permission');
                        $gluu_config = json_decode($this->gluu_db_query_update('gluu_config', json_encode($config)),true);
                    }else{
                        $this->gluu_db_query_update('gluu_new_role', json_encode(null));
                    }
                }
                if (empty($_POST['gluu_oxd_port'])) {
                    $this->gluu_db_query_update('message_error', 'All the fields are required. Please enter valid entries.');
                    header('Location: '.$base_url.'?_task=settings&_action=plugin.gluu_sso');return;
                }
                if (intval($_POST['gluu_oxd_port']) > 65535 && intval($_POST['gluu_oxd_port']) < 0) {
                    $this->gluu_db_query_update('message_error', 'Enter your oxd host port (Min. number 1, Max. number 65535)');
                    header('Location: '.$base_url.'?_task=settings&_action=plugin.gluu_sso');return;
                }
                if  (!empty($_POST['gluu_provider'])) {
                    if (filter_var($_POST['gluu_provider'], FILTER_VALIDATE_URL) === false) {
                        $this->gluu_db_query_update('message_error', 'Please enter valid OpenID Provider URI.');
                        header('Location: '.$base_url.'?_task=settings&_action=plugin.gluu_sso');return;
                    }
                }
                if  (!empty($_POST['gluu_custom_logout'])) {
                    if (filter_var($_POST['gluu_custom_logout'], FILTER_VALIDATE_URL) === false) {
                        $this->gluu_db_query_update('message_error', 'Please enter valid Custom URI.');
                    }else{
                        $this->gluu_db_query_update('gluu_custom_logout', $_POST['gluu_custom_logout']);
                    }
                }
                else{
                    $this->gluu_db_query_update('gluu_custom_logout', '');
                }

                if (isset($_POST['gluu_provider']) and !empty($_POST['gluu_provider'])) {

                    $gluu_provider = $_POST['gluu_provider'];
                    $gluu_provider = $this->gluu_db_query_update('gluu_provider', $gluu_provider);
                    $arrContextOptions=array(
                        "ssl"=>array(
                            "verify_peer"=>false,
                            "verify_peer_name"=>false,
                        ),
                    );
                    $json = file_get_contents($gluu_provider.'/.well-known/openid-configuration', false, stream_context_create($arrContextOptions));

                    $obj = json_decode($json);
                    if(!empty($obj->userinfo_endpoint)){

                        if(empty($obj->registration_endpoint)){
                            $this->gluu_db_query_update('message_success', "Please enter your client_id and client_secret.");
                            $gluu_config = json_encode(array(
                                "gluu_oxd_port" =>$_POST['gluu_oxd_port'],
                                "admin_email" => $_SESSION['username'],
                                "authorization_redirect_uri" => $base_url.'?_action=plugin.gluu_sso-login-from-gluu',
                                "post_logout_redirect_uri" => $base_url.'?_task=logout&logout=fromop',
                                "config_scopes" => ["openid","profile","email","imapData"],
                                "gluu_client_id" => "",
                                "gluu_client_secret" => "",
                                "config_acr" => []
                            ));
                            if($_POST['gluu_users_can_register']==2){
                                $config = json_decode($this->gluu_db_query_select('gluu_config'),true);
                                array_push($config['config_scopes'],'permission');
                                $gluu_config = json_decode($this->gluu_db_query_update('gluu_config', json_encode($config)),true);
                            }
                            $gluu_config = json_decode($this->gluu_db_query_update('gluu_config', $gluu_config),true);
                            if(isset($_POST['gluu_client_id']) and !empty($_POST['gluu_client_id']) and
                                isset($_POST['gluu_client_secret']) and !empty($_POST['gluu_client_secret'])){
                                $gluu_config = json_encode(array(
                                    "gluu_oxd_port" =>$_POST['gluu_oxd_port'],
                                    "admin_email" => $_SESSION['username'],
                                    "authorization_redirect_uri" => $base_url.'?_action=plugin.gluu_sso-login-from-gluu',
                                    "post_logout_redirect_uri" => $base_url.'?_task=logout&logout=fromop',
                                    "config_scopes" => ["openid","profile","email","imapData"],
                                    "gluu_client_id" => $_POST['gluu_client_id'],
                                    "gluu_client_secret" => $_POST['gluu_client_secret'],
                                    "config_acr" => []
                                ));
                                $gluu_config = json_decode($this->gluu_db_query_update('gluu_config', $gluu_config),true);
                                if($_POST['gluu_users_can_register']==2){
                                    $config = json_decode($this->gluu_db_query_select('gluu_config'),true);
                                    array_push($config['config_scopes'],'permission');
                                    $gluu_config = json_decode($this->gluu_db_query_update('gluu_config', json_encode($config)),true);
                                }
                                if(!$this->gluu_is_port_working()){
                                    $this->gluu_db_query_update('message_error', 'Can not connect to the oxd server. Please check the oxd-config.json file to make sure you have entered the correct port and the oxd server is operational.');
                                    header('Location: '.$base_url.'?_task=settings&_action=plugin.gluu_sso');return;
                                }
                                $register_site = new Register_site();
                                $register_site->setRequestOpHost($gluu_provider);
                                $register_site->setRequestAuthorizationRedirectUri($gluu_config['authorization_redirect_uri']);
                                $register_site->setRequestLogoutRedirectUri($gluu_config['post_logout_redirect_uri']);
                                $register_site->setRequestContacts([$gluu_config['admin_email']]);
                                $register_site->setRequestClientLogoutUri($gluu_config['post_logout_redirect_uri']);
                                $get_scopes = json_encode($obj->scopes_supported);
                                if(!empty($obj->acr_values_supported)){
                                    $get_acr = json_encode($obj->acr_values_supported);
                                    $get_acr = $this->gluu_db_query_update('gluu_acr', $get_acr);
                                    $register_site->setRequestAcrValues($gluu_config['config_acr']);
                                }
                                else{
                                    $register_site->setRequestAcrValues($gluu_config['config_acr']);
                                }
                                if(!empty($obj->scopes_supported)){
                                    $get_scopes = json_encode($obj->scopes_supported);
                                    $get_scopes = $this->gluu_db_query_update('gluu_scopes', $get_scopes);
                                    $register_site->setRequestScope($obj->scopes_supported);
                                }
                                else{
                                    $register_site->setRequestScope($gluu_config['config_scopes']);
                                }
                                $register_site->setRequestClientId($gluu_config['gluu_client_id']);
                                $register_site->setRequestClientSecret($gluu_config['gluu_client_secret']);
                                $status = $register_site->request();
                                if ($status['message'] == 'invalid_op_host') {
                                    $this->gluu_db_query_update('message_error', "ERROR: OpenID Provider host is required if you don\'t provide it in oxd-default-site-config.json");
                                    header('Location: '.$base_url.'?_task=settings&_action=plugin.gluu_sso');return;
                                }
                                if (!$status['status']) {
                                    $this->gluu_db_query_update('message_error', 'Can not connect to the oxd server. Please check the oxd-config.json file to make sure you have entered the correct port and the oxd server is operational.');
                                    header('Location: '.$base_url.'?_task=settings&_action=plugin.gluu_sso');return;
                                }
                                if ($status['message'] == 'internal_error') {
                                    $this->gluu_db_query_update('message_error', 'ERROR: '.$status['error_message']);
                                    header('Location: '.$base_url.'?_task=settings&_action=plugin.gluu_sso');return;
                                }
                                $gluu_oxd_id = $register_site->getResponseOxdId();
                                //var_dump($register_site->getResponseObject());exit;
                                if ($gluu_oxd_id) {
                                    $gluu_oxd_id = $this->gluu_db_query_update('gluu_oxd_id', $gluu_oxd_id);
                                    $gluu_provider = $register_site->getResponseOpHost();
                                    $gluu_provider = $this->gluu_db_query_update('gluu_provider', $gluu_provider);
                                    $this->gluu_db_query_update('message_success', 'Your settings are saved successfully.');
                                    header('Location: '.$base_url.'?_task=settings&_action=plugin.gluu_sso');return;
                                } else {
                                    $this->gluu_db_query_update('message_error', "ERROR: OpenID Provider host is required if you don\'t provide it in oxd-default-site-config.json");
                                    header('Location: '.$base_url.'?_task=settings&_action=plugin.gluu_sso');return;
                                }
                            }
                            else{
                                $this->gluu_db_query_update('openid_error', 'Error505.');
                                header('Location: '.$base_url.'?_task=settings&_action=plugin.gluu_sso');return;
                            }
                        }
                        else{

                            $gluu_config = json_encode(array(
                                "gluu_oxd_port" =>$_POST['gluu_oxd_port'],
                                "admin_email" => $_SESSION['username'],
                                "authorization_redirect_uri" => $base_url.'?_action=plugin.gluu_sso-login-from-gluu',
                                "post_logout_redirect_uri" => $base_url.'?_task=logout&logout=fromop',
                                "config_scopes" => ["openid","profile","email","imapData"],
                                "gluu_client_id" => "",
                                "gluu_client_secret" => "",
                                "config_acr" => []
                            ));
                            $gluu_config = json_decode($this->gluu_db_query_update('gluu_config', $gluu_config),true);
                            if($_POST['gluu_users_can_register']==2){
                                $config = json_decode($this->gluu_db_query_select('gluu_config'),true);
                                array_push($config['config_scopes'],'permission');
                                $gluu_config = json_decode($this->gluu_db_query_update('gluu_config', json_encode($config)),true);
                            }

                            if(!$this->gluu_is_port_working()){
                                $this->gluu_db_query_update('message_error', 'Can not connect to the oxd server. Please check the oxd-config.json file to make sure you have entered the correct port and the oxd server is operational.');
                                header('Location: '.$base_url.'?_task=settings&_action=plugin.gluu_sso');return;
                            }

                            $register_site = new Register_site();
                            $register_site->setRequestOpHost($gluu_provider);
                            $register_site->setRequestAuthorizationRedirectUri($gluu_config['authorization_redirect_uri']);
                            $register_site->setRequestLogoutRedirectUri($gluu_config['post_logout_redirect_uri']);
                            $register_site->setRequestContacts([$gluu_config['admin_email']]);
                            $register_site->setRequestClientLogoutUri($gluu_config['post_logout_redirect_uri']);
                            $get_scopes = json_encode($obj->scopes_supported);
                            if(!empty($obj->acr_values_supported)){
                                $get_acr = json_encode($obj->acr_values_supported);
                                $get_acr = json_decode($this->gluu_db_query_update('gluu_acr', $get_acr));
                                $register_site->setRequestAcrValues($gluu_config['config_acr']);
                            }
                            else{
                                $register_site->setRequestAcrValues($gluu_config['config_acr']);
                            }
                            if(!empty($obj->scopes_supported)){
                                $get_scopes = json_encode($obj->scopes_supported);
                                $get_scopes = json_decode($this->gluu_db_query_update('gluu_scopes', $get_scopes));
                                $register_site->setRequestScope($obj->scopes_supported);
                            }
                            else{
                                $register_site->setRequestScope($gluu_config['config_scopes']);
                            }
                            $status = $register_site->request();
                            //var_dump($status);exit;
                            if ($status['message'] == 'invalid_op_host') {
                                $this->gluu_db_query_update('message_error', "ERROR: OpenID Provider host is required if you don\'t provide it in oxd-default-site-config.json");
                                header('Location: '.$base_url.'?_task=settings&_action=plugin.gluu_sso');return;
                            }
                            if (!$status['status']) {
                                $this->gluu_db_query_update('message_error', 'Can not connect to the oxd server. Please check the oxd-config.json file to make sure you have entered the correct port and the oxd server is operational.');
                                header('Location: '.$base_url.'?_task=settings&_action=plugin.gluu_sso');return;
                            }
                            if ($status['message'] == 'internal_error') {
                                $this->gluu_db_query_update('message_error', 'ERROR: '.$status['error_message']);
                                header('Location: '.$base_url.'?_task=settings&_action=plugin.gluu_sso');return;
                            }
                            $gluu_oxd_id = $register_site->getResponseOxdId();
                            if ($gluu_oxd_id) {
                                $this->gluu_db_query_update('gluu_oxd_id', $gluu_oxd_id);
                                $register_site->getResponseOpHost();
                                $this->gluu_db_query_update('gluu_provider', $gluu_provider);
                                $this->gluu_db_query_update('message_success', 'Your settings are saved successfully.');

                                header('Location: '.$base_url.'?_task=settings&_action=plugin.gluu_sso');
                                return;
                            }
                            else {
                                $this->gluu_db_query_update('message_error', "ERROR: OpenID Provider host is required if you don\'t provide it in oxd-default-site-config.json");
                                header('Location: '.$base_url.'?_task=settings&_action=plugin.gluu_sso');return;
                            }
                        }
                    }
                    else{
                        $this->gluu_db_query_update('message_error', 'Please enter correct URI of the OpenID Provider');
                        header('Location: '.$base_url.'?_task=settings&_action=plugin.gluu_sso');return;
                    }
                }
                else{
                    $gluu_config = json_encode(array(
                        "gluu_oxd_port" =>$_POST['gluu_oxd_port'],
                        "admin_email" => $_SESSION['username'],
                        "authorization_redirect_uri" => $base_url.'?_action=plugin.gluu_sso-login-from-gluu',
                        "post_logout_redirect_uri" => $base_url.'?_task=logout&logout=fromop',
                        "config_scopes" => ["openid","profile","email","imapData"],
                        "gluu_client_id" => "",
                        "gluu_client_secret" => "",
                        "config_acr" => []
                    ));
                    $gluu_config = json_decode($this->gluu_db_query_update('gluu_config', $gluu_config),true);
                    if($_POST['gluu_users_can_register']==2){
                        $config = json_decode($this->gluu_db_query_select('gluu_config'),true);
                        array_push($config['config_scopes'],'permission');
                        $gluu_config = json_decode($this->gluu_db_query_update('gluu_config', json_encode($config)),true);
                    }
                    if(!$this->gluu_is_port_working()){
                        $this->gluu_db_query_update('message_error', 'Can not connect to the oxd server. Please check the oxd-config.json file to make sure you have entered the correct port and the oxd server is operational.');

                        header('Location: '.$base_url.'?_task=settings&_action=plugin.gluu_sso');return;
                    }
                    $register_site = new Register_site();
                    $register_site->setRequestAuthorizationRedirectUri($gluu_config['authorization_redirect_uri']);
                    $register_site->setRequestLogoutRedirectUri($gluu_config['post_logout_redirect_uri']);
                    $register_site->setRequestContacts([$gluu_config['admin_email']]);
                    $register_site->setRequestAcrValues($gluu_config['config_acr']);
                    $register_site->setRequestScope($gluu_config['config_scopes']);
                    $register_site->setRequestClientLogoutUri($gluu_config['post_logout_redirect_uri']);
                    $status = $register_site->request();

                    if ($status['message'] == 'invalid_op_host') {
                        $this->gluu_db_query_update('message_error', "ERROR: OpenID Provider host is required if you don\'t provide it in oxd-default-site-config.json");
                        header('Location: '.$base_url.'?_task=settings&_action=plugin.gluu_sso');
                        return;
                    }
                    if (!$status['status']) {
                        $this->gluu_db_query_update('message_error', 'Can not connect to the oxd server. Please check the oxd-config.json file to make sure you have entered the correct port and the oxd server is operational.');
                        header('Location: '.$base_url.'?_task=settings&_action=plugin.gluu_sso');
                        return;
                    }
                    if ($status['message'] == 'internal_error') {
                        $this->gluu_db_query_update('message_error', 'ERROR: '.$status['error_message']);
                        header('Location: '.$base_url.'?_task=settings&_action=plugin.gluu_sso');
                        return;
                    }
                    $gluu_oxd_id = $register_site->getResponseOxdId();
                    if ($gluu_oxd_id) {
                        $gluu_oxd_id = $this->gluu_db_query_update('gluu_oxd_id', $gluu_oxd_id);
                        $gluu_provider = $register_site->getResponseOpHost();
                        $gluu_provider = $this->gluu_db_query_update('gluu_provider', $gluu_provider);
                        $arrContextOptions=array(
                            "ssl"=>array(
                                "verify_peer"=>false,
                                "verify_peer_name"=>false,
                            ),
                        );
                        $json = file_get_contents($gluu_provider.'/.well-known/openid-configuration', false, stream_context_create($arrContextOptions));
                        $obj = json_decode($json);
                        if(!$this->gluu_is_port_working()){
                            $this->gluu_db_query_update('message_error', 'Can not connect to the oxd server. Please check the oxd-config.json file to make sure you have entered the correct port and the oxd server is operational.');
                            header('Location: '.$base_url.'?_task=settings&_action=plugin.gluu_sso');return;
                        }
                        $register_site = new Register_site();
                        $register_site->setRequestOpHost($gluu_provider);
                        $register_site->setRequestAuthorizationRedirectUri($gluu_config['authorization_redirect_uri']);
                        $register_site->setRequestLogoutRedirectUri($gluu_config['post_logout_redirect_uri']);
                        $register_site->setRequestContacts([$gluu_config['admin_email']]);
                        $register_site->setRequestClientLogoutUri($gluu_config['post_logout_redirect_uri']);

                        $get_scopes = json_encode($obj->scopes_supported);
                        if(!empty($obj->acr_values_supported)){
                            $get_acr = json_encode($obj->acr_values_supported);
                            $get_acr = $this->gluu_db_query_update('gluu_acr', $get_acr);
                            $register_site->setRequestAcrValues($gluu_config['config_acr']);
                        }
                        else{
                            $register_site->setRequestAcrValues($gluu_config['config_acr']);
                        }
                        if(!empty($obj->scopes_supported)){
                            $get_scopes = json_encode($obj->scopes_supported);
                            $get_scopes = $this->gluu_db_query_update('gluu_scopes', $get_scopes);
                            $register_site->setRequestScope($obj->scopes_supported);
                        }
                        else{
                            $register_site->setRequestScope($gluu_config['config_scopes']);
                        }
                        $status = $register_site->request();
                        if ($status['message'] == 'invalid_op_host') {
                            $this->gluu_db_query_update('message_error', "ERROR: OpenID Provider host is required if you don\'t provide it in oxd-default-site-config.json");
                            header('Location: '.$base_url.'?_task=settings&_action=plugin.gluu_sso');return;
                        }
                        if (!$status['status']) {
                            $this->gluu_db_query_update('message_error', 'Can not connect to the oxd server. Please check the oxd-config.json file to make sure you have entered the correct port and the oxd server is operational.');
                            header('Location: '.$base_url.'?_task=settings&_action=plugin.gluu_sso');return;
                        }
                        if ($status['message'] == 'internal_error') {
                            $this->gluu_db_query_update('message_error', 'ERROR: '.$status['error_message']);
                            header('Location: '.$base_url.'?_task=settings&_action=plugin.gluu_sso');return;
                        }
                        $gluu_oxd_id = $register_site->getResponseOxdId();
                        if ($gluu_oxd_id) {
                            $gluu_oxd_id = $this->gluu_db_query_update('gluu_oxd_id', $gluu_oxd_id);
                            $this->gluu_db_query_update('message_success', 'Your settings are saved successfully.');
                            header('Location: '.$base_url.'?_task=settings&_action=plugin.gluu_sso');return;
                        }
                        else {
                            $this->gluu_db_query_update('message_error', "ERROR: OpenID Provider host is required if you don\'t provide it in oxd-default-site-config.json");
                            header('Location: '.$base_url.'?_task=settings&_action=plugin.gluu_sso');return;
                        }
                    }
                    else {
                        $this->gluu_db_query_update('message_error', "ERROR: OpenID Provider host is required if you don\'t provide it in oxd-default-site-config.json");
                        header('Location: '.$base_url.'?_task=settings&_action=plugin.gluu_sso');return;
                    }
                }
            }
            else if( isset( $_REQUEST['form_key'] ) and strpos( $_REQUEST['form_key'], 'general_oxd_edit' ) !== false ) {
                if(!isset($_SERVER['HTTPS']) or $_SERVER['HTTPS'] != "on") {
                    $this->gluu_db_query_update('message_error', 'OpenID Connect requires https. This plugin will not work if your website uses http only.');
                    header('Location: '.$base_url.'?_task=settings&_action=plugin.gluu_sso');return;
                }
                if($_POST['gluu_users_can_register']==1){
                    $this->gluu_db_query_update('gluu_users_can_register', $_POST['gluu_users_can_register']);
                    if(!empty(array_values(array_filter($_POST['gluu_new_role'])))){
                        $this->gluu_db_query_update('gluu_new_role', json_encode(array_values(array_filter($_POST['gluu_new_role']))));
                        $config = json_decode($this->gluu_db_query_select('gluu_config'),true);
                        array_push($config['config_scopes'],'permission');
                        $gluu_config = json_decode($this->gluu_db_query_update('gluu_config', json_encode($config)),true);
                    }else{
                        $this->gluu_db_query_update('gluu_new_role', json_encode(null));
                    }
                }
                if($_POST['gluu_users_can_register']==2){
                    $this->gluu_db_query_update('gluu_users_can_register', 2);

                    if(!empty(array_values(array_filter($_POST['gluu_new_role'])))){
                        $this->gluu_db_query_update('gluu_new_role', json_encode(array_values(array_filter($_POST['gluu_new_role']))));
                        $config = json_decode($this->gluu_db_query_select('gluu_config'),true);
                        array_push($config['config_scopes'],'permission');
                        $gluu_config = json_decode($this->gluu_db_query_update('gluu_config', json_encode($config)),true);
                    }else{
                        $this->gluu_db_query_update('gluu_new_role', json_encode(null));
                    }
                }
                if (empty($_POST['gluu_oxd_port'])) {
                    $this->gluu_db_query_update('message_error', 'All the fields are required. Please enter valid entries.');
                    header('Location: '.$base_url.'?_task=settings&_action=plugin.gluu_sso');return;
                }
                if (intval($_POST['gluu_oxd_port']) > 65535 && intval($_POST['gluu_oxd_port']) < 0) {
                    $this->gluu_db_query_update('message_error', 'Enter your oxd host port (Min. number 1, Max. number 65535)');
                    header('Location: '.$base_url.'?_task=settings&_action=plugin.gluu_sso');return;
                }
                
                if  (!empty($_POST['gluu_custom_logout'])) {
                    if (filter_var($_POST['gluu_custom_logout'], FILTER_VALIDATE_URL) === false) {
                        $this->gluu_db_query_update('message_error', 'Please enter valid Custom URI.');
                    }else{
                        $this->gluu_db_query_update('gluu_custom_logout', $_POST['gluu_custom_logout']);
                    }
                }
                else{
                    $this->gluu_db_query_update('gluu_custom_logout', '');
                }
                $gluu_provider = $this->gluu_db_query_select('gluu_provider');
                $arrContextOptions=array(
                    "ssl"=>array(
                        "verify_peer"=>false,
                        "verify_peer_name"=>false,
                    ),
                );
                $json = file_get_contents($gluu_provider.'/.well-known/openid-configuration', false, stream_context_create($arrContextOptions));

                $obj = json_decode($json);
                if(!empty($obj->userinfo_endpoint)){

                    if(empty($obj->registration_endpoint)){
                        $this->gluu_db_query_update('message_success', "Please enter your client_id and client_secret.");
                        $gluu_config = json_encode(array(
                            "gluu_oxd_port" =>$_POST['gluu_oxd_port'],
                            "admin_email" => $_SESSION['username'],
                            "authorization_redirect_uri" => $base_url.'?_action=plugin.gluu_sso-login-from-gluu',
                            "post_logout_redirect_uri" => $base_url.'?_task=logout&logout=fromop',
                            "config_scopes" => ["openid","profile","email","imapData"],
                            "gluu_client_id" => "",
                            "gluu_client_secret" => "",
                            "config_acr" => []
                        ));
                        if($_POST['gluu_users_can_register']==2){
                            $config = json_decode($this->gluu_db_query_select('gluu_config'),true);
                            array_push($config['config_scopes'],'permission');
                            $gluu_config = json_decode($this->gluu_db_query_update('gluu_config', json_encode($config)),true);
                        }
                        $gluu_config = json_decode($this->gluu_db_query_update('gluu_config', $gluu_config),true);
                        if(isset($_POST['gluu_client_id']) and !empty($_POST['gluu_client_id']) and
                            isset($_POST['gluu_client_secret']) and !empty($_POST['gluu_client_secret'])){
                            $gluu_config = json_encode(array(
                                "gluu_oxd_port" =>$_POST['gluu_oxd_port'],
                                "admin_email" => $_SESSION['username'],
                                "authorization_redirect_uri" => $base_url.'?_action=plugin.gluu_sso-login-from-gluu',
                                "post_logout_redirect_uri" => $base_url.'?_task=logout&logout=fromop',
                                "config_scopes" => ["openid","profile","email","imapData"],
                                "gluu_client_id" => $_POST['gluu_client_id'],
                                "gluu_client_secret" => $_POST['gluu_client_secret'],
                                "config_acr" => []
                            ));
                            $gluu_config = json_decode($this->gluu_db_query_update('gluu_config', $gluu_config),true);
                            if($_POST['gluu_users_can_register']==2){
                                $config = json_decode($this->gluu_db_query_select('gluu_config'),true);
                                array_push($config['config_scopes'],'permission');
                                $gluu_config = json_decode($this->gluu_db_query_update('gluu_config', json_encode($config)),true);
                            }
                            if(!$this->gluu_is_port_working()){
                                $this->gluu_db_query_update('message_error', 'Can not connect to the oxd server. Please check the oxd-config.json file to make sure you have entered the correct port and the oxd server is operational.');
                                header('Location: '.$base_url.'?_task=settings&_action=plugin.gluu_sso');return;
                            }
                            $register_site = new Register_site();
                            $register_site->setRequestOpHost($gluu_provider);
                            $register_site->setRequestAuthorizationRedirectUri($gluu_config['authorization_redirect_uri']);
                            $register_site->setRequestLogoutRedirectUri($gluu_config['post_logout_redirect_uri']);
                            $register_site->setRequestContacts([$gluu_config['admin_email']]);
                            $register_site->setRequestClientLogoutUri($gluu_config['post_logout_redirect_uri']);
                            $get_scopes = json_encode($obj->scopes_supported);
                            if(!empty($obj->acr_values_supported)){
                                $get_acr = json_encode($obj->acr_values_supported);
                                $get_acr = $this->gluu_db_query_update('gluu_acr', $get_acr);
                                $register_site->setRequestAcrValues($gluu_config['config_acr']);
                            }
                            else{
                                $register_site->setRequestAcrValues($gluu_config['config_acr']);
                            }
                            if(!empty($obj->scopes_supported)){
                                $get_scopes = json_encode($obj->scopes_supported);
                                $get_scopes = $this->gluu_db_query_update('gluu_scopes', $get_scopes);
                                $register_site->setRequestScope($obj->scopes_supported);
                            }
                            else{
                                $register_site->setRequestScope($gluu_config['config_scopes']);
                            }
                            $register_site->setRequestClientId($gluu_config['gluu_client_id']);
                            $register_site->setRequestClientSecret($gluu_config['gluu_client_secret']);
                            $status = $register_site->request();
                            if ($status['message'] == 'invalid_op_host') {
                                $this->gluu_db_query_update('message_error', "ERROR: OpenID Provider host is required if you don\'t provide it in oxd-default-site-config.json");
                                header('Location: '.$base_url.'?_task=settings&_action=plugin.gluu_sso');return;
                            }
                            if (!$status['status']) {
                                $this->gluu_db_query_update('message_error', 'Can not connect to the oxd server. Please check the oxd-config.json file to make sure you have entered the correct port and the oxd server is operational.');
                                header('Location: '.$base_url.'?_task=settings&_action=plugin.gluu_sso');return;
                            }
                            if ($status['message'] == 'internal_error') {
                                $this->gluu_db_query_update('message_error', 'ERROR: '.$status['error_message']);
                                header('Location: '.$base_url.'?_task=settings&_action=plugin.gluu_sso');return;
                            }
                            $gluu_oxd_id = $register_site->getResponseOxdId();
                            //var_dump($register_site->getResponseObject());exit;
                            if ($gluu_oxd_id) {
                                $gluu_oxd_id = $this->gluu_db_query_update('gluu_oxd_id', $gluu_oxd_id);
                                $gluu_provider = $register_site->getResponseOpHost();
                                $gluu_provider = $this->gluu_db_query_update('gluu_provider', $gluu_provider);
                                $this->gluu_db_query_update('message_success', 'Your settings are saved successfully.');
                                header('Location: '.$base_url.'?_task=settings&_action=plugin.gluu_sso');return;
                            } else {
                                $this->gluu_db_query_update('message_error', "ERROR: OpenID Provider host is required if you don\'t provide it in oxd-default-site-config.json");
                                header('Location: '.$base_url.'?_task=settings&_action=plugin.gluu_sso');return;
                            }
                        }
                        else{
                            $this->gluu_db_query_update('openid_error', 'Error505.');
                            header('Location: '.$base_url.'?_task=settings&_action=plugin.gluu_sso');return;
                        }
                    }
                    else{

                        $gluu_config = json_encode(array(
                            "gluu_oxd_port" =>$_POST['gluu_oxd_port'],
                            "admin_email" => $_SESSION['username'],
                            "authorization_redirect_uri" => $base_url.'?_action=plugin.gluu_sso-login-from-gluu',
                            "post_logout_redirect_uri" => $base_url.'?_task=logout&logout=fromop',
                            "config_scopes" => ["openid","profile","email","imapData"],
                            "gluu_client_id" => "",
                            "gluu_client_secret" => "",
                            "config_acr" => []
                        ));
                        $gluu_config = json_decode($this->gluu_db_query_update('gluu_config', $gluu_config),true);
                        if($_POST['gluu_users_can_register']==2){
                            $config = json_decode($this->gluu_db_query_select('gluu_config'),true);
                            array_push($config['config_scopes'],'permission');
                            $gluu_config = json_decode($this->gluu_db_query_update('gluu_config', json_encode($config)),true);
                        }

                        if(!$this->gluu_is_port_working()){
                            $this->gluu_db_query_update('message_error', 'Can not connect to the oxd server. Please check the oxd-config.json file to make sure you have entered the correct port and the oxd server is operational.');
                            header('Location: '.$base_url.'?_task=settings&_action=plugin.gluu_sso');return;
                        }

                        $register_site = new Register_site();
                        $register_site->setRequestOpHost($gluu_provider);
                        $register_site->setRequestAuthorizationRedirectUri($gluu_config['authorization_redirect_uri']);
                        $register_site->setRequestLogoutRedirectUri($gluu_config['post_logout_redirect_uri']);
                        $register_site->setRequestContacts([$gluu_config['admin_email']]);
                        $register_site->setRequestClientLogoutUri($gluu_config['post_logout_redirect_uri']);
                        $get_scopes = json_encode($obj->scopes_supported);
                        if(!empty($obj->acr_values_supported)){
                            $get_acr = json_encode($obj->acr_values_supported);
                            $get_acr = json_decode($this->gluu_db_query_update('gluu_acr', $get_acr));
                            $register_site->setRequestAcrValues($gluu_config['config_acr']);
                        }
                        else{
                            $register_site->setRequestAcrValues($gluu_config['config_acr']);
                        }
                        if(!empty($obj->scopes_supported)){
                            $get_scopes = json_encode($obj->scopes_supported);
                            $get_scopes = json_decode($this->gluu_db_query_update('gluu_scopes', $get_scopes));
                            $register_site->setRequestScope($obj->scopes_supported);
                        }
                        else{
                            $register_site->setRequestScope($gluu_config['config_scopes']);
                        }
                        $status = $register_site->request();
                        //var_dump($status);exit;
                        if ($status['message'] == 'invalid_op_host') {
                            $this->gluu_db_query_update('message_error', "ERROR: OpenID Provider host is required if you don\'t provide it in oxd-default-site-config.json");
                            header('Location: '.$base_url.'?_task=settings&_action=plugin.gluu_sso');return;
                        }
                        if (!$status['status']) {
                            $this->gluu_db_query_update('message_error', 'Can not connect to the oxd server. Please check the oxd-config.json file to make sure you have entered the correct port and the oxd server is operational.');
                            header('Location: '.$base_url.'?_task=settings&_action=plugin.gluu_sso');return;
                        }
                        if ($status['message'] == 'internal_error') {
                            $this->gluu_db_query_update('message_error', 'ERROR: '.$status['error_message']);
                            header('Location: '.$base_url.'?_task=settings&_action=plugin.gluu_sso');return;
                        }
                        $gluu_oxd_id = $register_site->getResponseOxdId();
                        if ($gluu_oxd_id) {
                            $this->gluu_db_query_update('gluu_oxd_id', $gluu_oxd_id);
                            $register_site->getResponseOpHost();
                            $this->gluu_db_query_update('gluu_provider', $gluu_provider);
                            $this->gluu_db_query_update('message_success', 'Your settings are saved successfully.');

                            header('Location: '.$base_url.'?_task=settings&_action=plugin.gluu_sso');
                            return;
                        }
                        else {
                            $this->gluu_db_query_update('message_error', "ERROR: OpenID Provider host is required if you don\'t provide it in oxd-default-site-config.json");
                            header('Location: '.$base_url.'?_task=settings&_action=plugin.gluu_sso');return;
                        }
                    }
                }
                else{
                    $this->gluu_db_query_update('message_error', 'Please enter correct URI of the OpenID Provider');
                    header('Location: '.$base_url.'?_task=settings&_action=plugin.gluu_sso');return;
                }
            }
            else if( isset( $_REQUEST['submit'] ) and strpos( $_REQUEST['submit'], 'delete' )  !== false and !empty($_REQUEST['submit'])) {
                $db->query("DROP TABLE IF EXISTS `gluu_table`;");
                $result = $db->query("CREATE TABLE IF NOT EXISTS `gluu_table` (

              `gluu_action` varchar(255) NOT NULL,
              `gluu_value` longtext NOT NULL,
              UNIQUE(`gluu_action`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
                $this->gluu_db_query_insert('message_success', 'Configurations deleted Successfully.');
                $this->gluu_db_query_insert('message_error', '');
                header('Location: '.$base_url.'?_task=settings&_action=plugin.gluu_sso');return;
            }
            else if( isset( $_REQUEST['form_key'] ) and strpos( $_REQUEST['form_key'], 'general_oxd_id_reset' )!== false and !empty($_REQUEST['resetButton'])) {
                $db->query("DROP TABLE IF EXISTS `gluu_table`;");
                $result = $db->query("CREATE TABLE IF NOT EXISTS `gluu_table` (

              `gluu_action` varchar(255) NOT NULL,
              `gluu_value` longtext NOT NULL,
              UNIQUE(`gluu_action`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
                $this->gluu_db_query_insert('message_success', 'Configurations deleted Successfully.');
                $this->gluu_db_query_insert('message_error', '');
                header('Location: '.$base_url.'?_task=settings&_action=plugin.gluu_sso');return;
            }
            else if( isset( $_REQUEST['form_key'] ) and strpos( $_REQUEST['form_key'], 'openid_config_page' ) !== false ) {
                $params = $_REQUEST;
                $message_success = '';
                $message_error = '';

                if($_POST['send_user_type']){
                    $gluu_auth_type = $_POST['send_user_type'];
                    $gluu_auth_type = $this->gluu_db_query_update('gluu_auth_type', $gluu_auth_type);
                }else{
                    $gluu_auth_type = $this->gluu_db_query_update('gluu_auth_type', 'default');
                }
                $gluu_send_user_check = $_POST['send_user_check'];
                $gluu_send_user_check = $this->gluu_db_query_update('gluu_send_user_check', $gluu_send_user_check);

                if(!empty($params['scope']) && isset($params['scope'])){
                    $gluu_config =   json_decode($this->gluu_db_query_select("gluu_config"),true);
                    $gluu_config['config_scopes'] = $params['scope'];
                    $gluu_config = json_encode($gluu_config);
                    $gluu_config = json_decode($this->gluu_db_query_update('gluu_config', $gluu_config),true);
                }
                if(!empty($params['scope_name']) && isset($params['scope_name'])){
                    $get_scopes =   json_decode($this->gluu_db_query_select("gluu_scopes"),true);
                    foreach($params['scope_name'] as $scope){
                        if($scope && !in_array($scope,$get_scopes)){
                            array_push($get_scopes, $scope);
                        }
                    }
                    $get_scopes = json_encode($get_scopes);
                    $get_scopes = json_decode($this->gluu_db_query_update('gluu_scopes', $get_scopes),true);
                }
                $gluu_acr              = json_decode($this->gluu_db_query_select('gluu_acr'),true);

                if(!empty($params['acr_name']) && isset($params['acr_name'])){
                    $get_acr =   json_decode($this->gluu_db_query_select("gluu_acr"),true);
                    foreach($params['acr_name'] as $scope){
                        if($scope && !in_array($scope,$get_acr)){
                            array_push($get_acr, $scope);
                        }
                    }
                    $get_acr = json_encode($get_acr);
                    $get_acr = json_decode($this->gluu_db_query_update('gluu_acr', $get_acr),true);
                }
                $gluu_config =   json_decode($this->gluu_db_query_select("gluu_config"),true);
                $gluu_oxd_id =   $this->gluu_db_query_select("gluu_oxd_id");
                if(!$this->gluu_is_port_working()){
                    $this->gluu_db_query_update('message_error', 'Can not connect to the oxd server. Please check the oxd-config.json file to make sure you have entered the correct port and the oxd server is operational.');
                    header('Location: '.$base_url.'?_task=settings&_action=plugin.gluu_sso');return;
                }
                $update_site_registration = new Update_site_registration();
                $update_site_registration->setRequestOxdId($gluu_oxd_id);
                $update_site_registration->setRequestAcrValues($gluu_config['acr_values']);
                $update_site_registration->setRequestAuthorizationRedirectUri($gluu_config['authorization_redirect_uri']);
                $update_site_registration->setRequestLogoutRedirectUri($gluu_config['post_logout_redirect_uri']);
                $update_site_registration->setRequestContacts([$gluu_config['admin_email']]);
                $update_site_registration->setRequestClientLogoutUri($gluu_config['post_logout_redirect_uri']);
                $update_site_registration->setRequestScope($gluu_config['config_scopes']);
                $status = $update_site_registration->request();
                $new_oxd_id = $update_site_registration->getResponseOxdId();
                if($new_oxd_id){
                    $get_scopes = $this->gluu_db_query_update('gluu_oxd_id', $new_oxd_id);
                }

                $this->gluu_db_query_update('message_success', 'Your OpenID connect configuration has been saved.');
                header('Location: '.$base_url.'?_task=settings&_action=plugin.gluu_sso-openidconfig');return;
            }
            else if( isset( $_REQUEST['form_key_scope_delete'] ) and strpos( $_REQUEST['form_key_scope_delete'], 'form_key_scope_delete' ) !== false ) {
                $get_scopes =   json_decode($this->gluu_db_query_select('gluu_scopes'),true);
                $up_cust_sc =  array();
                foreach($get_scopes as $custom_scop){
                    if($custom_scop !=$_POST['delete_scope']){
                        array_push($up_cust_sc,$custom_scop);
                    }
                }
                $get_scopes = json_encode($up_cust_sc);
                $get_scopes = $this->gluu_db_query_update('gluu_scopes', $get_scopes);


                $gluu_config =   json_decode($this->gluu_db_query_select("gluu_config"),true);
                $up_cust_scope =  array();
                foreach($gluu_config['config_scopes'] as $custom_scop){
                    if($custom_scop !=$_POST['delete_scope']){
                        array_push($up_cust_scope,$custom_scop);
                    }
                }
                $gluu_config['config_scopes'] = $up_cust_scope;
                $gluu_config = json_encode($gluu_config);
                $gluu_config = json_decode($this->gluu_db_query_update('gluu_config', $gluu_config),true);
                return true;
            }
            else if (isset($_REQUEST['form_key_scope']) and strpos( $_REQUEST['form_key_scope'], 'oxd_openid_config_new_scope' ) !== false) {
                if ($this->gluu_is_oxd_registered()) {
                    if (!empty($_REQUEST['new_value_scope']) && isset($_REQUEST['new_value_scope'])) {

                        $get_scopes =   json_decode($this->gluu_db_query_select("gluu_scopes"),true);
                        if($_REQUEST['new_value_scope'] && !in_array($_REQUEST['new_value_scope'],$get_scopes)){
                            array_push($get_scopes, $_REQUEST['new_value_scope']);
                        }
                        $get_scopes = json_encode($get_scopes);
                        $this->gluu_db_query_update('gluu_scopes', $get_scopes);
                        return true;
                    }

                }
            }
        }
        else{
            header("Location: ".$base_url);
            return;
        }
        $RCMAIL->output->redirect('plugin.gluu_sso');
    }
    public function startup($args)
    {
        $base_url = $this->getBaseUrl();

        if(isset( $_REQUEST['_task'] ) and strpos( $_REQUEST['_task'], 'logout' ) !== false ){
            if(isset( $_REQUEST['logout'] ) and strpos( $_REQUEST['logout'], 'fromop' ) !== false ){
                session_start();
                session_destroy();
                $RCMAIL = rcmail::get_instance($GLOBALS['env']);
                $db = $RCMAIL->db;
                unset($_SESSION['user_oxd_access_token']);
                unset($_SESSION['user_oxd_id_token']);
                unset($_SESSION['session_state']);
                unset($_SESSION['state']);
                unset($_SESSION['session_in_op']);
                unset($_COOKIE['roundcube_sessauth']);
                unset($_COOKIE['roundcube_sessid']);
                $RCMAIL->kill_session();
                $gluu_custom_logout = $this->gluu_db_query_select('gluu_custom_logout');
                if(!empty($gluu_custom_logout)){
                    header("Location: $gluu_custom_logout");
                }else{
                    header('Location: '.$base_url);
                }
                exit;

            }
            else{
                session_start();
                require_once("GluuOxd_Openid/oxd-rp/Logout.php");
                $RCMAIL = rcmail::get_instance($GLOBALS['env']);
                $db = $RCMAIL->db;
                $oxd_id =  $this->gluu_db_query_select("gluu_oxd_id");

                $conf = json_decode($this->gluu_db_query_select("gluu_config"),true);;
                if(isset($_SESSION['session_in_op'])){
                    if(time()<(int)$_SESSION['session_in_op']) {

                        $gluu_provider = $this->gluu_db_query_select('gluu_provider');
                        $arrContextOptions=array(
                            "ssl"=>array(
                                "verify_peer"=>false,
                                "verify_peer_name"=>false,
                            ),
                        );
                        $json = file_get_contents($gluu_provider.'/.well-known/openid-configuration', false, stream_context_create($arrContextOptions));
                        $obj = json_decode($json);

                        $oxd_id = $this->gluu_db_query_select('gluu_oxd_id');
                        $gluu_config = json_decode($this->gluu_db_query_select('gluu_config'), true);
                        if (!empty($obj->end_session_endpoint ) or $gluu_provider == 'https://accounts.google.com') {
                            if (!empty($_SESSION['user_oxd_id_token'])) {
                                if ($oxd_id && $_SESSION['user_oxd_id_token'] && $_SESSION['session_in_op']) {
                                    $logout = new Logout();
                                    $logout->setRequestOxdId($oxd_id);
                                    $logout->setRequestIdToken($_SESSION['user_oxd_id_token']);
                                    $logout->setRequestPostLogoutRedirectUri($gluu_config['post_logout_redirect_uri']);
                                    $logout->setRequestSessionState($_SESSION['session_state']);
                                    $logout->setRequestState($_SESSION['state']);
                                    $logout->request();
                                    session_destroy();
                                    unset($_SESSION['user_oxd_access_token']);
                                    unset($_SESSION['user_oxd_id_token']);
                                    unset($_SESSION['session_state']);
                                    unset($_SESSION['state']);
                                    unset($_SESSION['session_in_op']);
                                    header("Location: " . $logout->getResponseObject()->data->uri);
                                    exit;
                                }
                            }
                        }
                        else {
                            session_destroy();
                            unset($_SESSION['user_oxd_access_token']);
                            unset($_SESSION['user_oxd_id_token']);
                            unset($_SESSION['session_state']);
                            unset($_SESSION['state']);
                            unset($_SESSION['session_in_op']);
                            $gluu_custom_logout = $this->gluu_db_query_select('gluu_custom_logout');
                            if(!empty($gluu_custom_logout)){
                                header("Location: $gluu_custom_logout");
                            }else{
                                header('Location: '.$base_url);
                            }
                            exit;
                        }
                    }
                }
            }
        }
        if(isset($_REQUEST['app_name']) && isset( $_REQUEST['_action'] ) and strpos( $_REQUEST['_action'], 'plugin.gluu_sso-login' )               !== false ){
            require_once("GluuOxd_Openid/oxd-rp/Get_authorization_url.php");
            $RCMAIL = rcmail::get_instance($GLOBALS['env']);
            $db = $RCMAIL->db;
            $oxd_id =  $this->gluu_db_query_select("gluu_oxd_id");
            $get_authorization_url = new Get_authorization_url();
            $get_authorization_url->setRequestOxdId($oxd_id);
            $get_authorization_url->setRequestAcrValues([$_REQUEST['app_name']]);
            $get_authorization_url->request();

            if($get_authorization_url->getResponseAuthorizationUrl()){
                header( "Location: ". $get_authorization_url->getResponseAuthorizationUrl() );
                exit;
            }else{
                echo '<p style="color: red">Sorry, but oxd server is not switched on!</p>';
            }

        }
        if(isset( $_REQUEST['_action'] ) and strpos( $_REQUEST['_action'], 'plugin.gluu_sso-login-from-gluu' )               !== false ){

            require_once("GluuOxd_Openid/oxd-rp/Get_tokens_by_code.php");
            require_once("GluuOxd_Openid/oxd-rp/Get_user_info.php");
            $RCMAIL = rcmail::get_instance($GLOBALS['env']);
            $db = $RCMAIL->db;
            $RCMAIL->kill_session();
            $oxd_id =  $this->gluu_db_query_select("gluu_oxd_id");

            $http = isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? "https://" : "http://";
            $parts = parse_url($http . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
            parse_str($parts['query'], $query);
            $config_option = json_decode($this->gluu_db_query_select("gluu_config"),true);;
            $get_tokens_by_code = new Get_tokens_by_code();
            $get_tokens_by_code->setRequestOxdId($oxd_id);
            $get_tokens_by_code->setRequestCode($_REQUEST['code']);
            $get_tokens_by_code->setRequestState($_REQUEST['state']);
            $get_tokens_by_code->request();
            $get_tokens_by_code_array = array();
            if(!empty($get_tokens_by_code->getResponseObject()->data->id_token_claims))
            {
                $get_tokens_by_code_array = $get_tokens_by_code->getResponseObject()->data->id_token_claims;
            }else{
                echo "<script type='application/javascript'>
					alert('Missing claims : Please talk to your organizational system administrator or try again.');
					location.href='".$base_url."';
				 </script>";
                exit;
            }
            $get_user_info = new Get_user_info();
            $get_user_info->setRequestOxdId($oxd_id);
            $get_user_info->setRequestAccessToken($get_tokens_by_code->getResponseAccessToken());
            $get_user_info->request();
            $get_user_info_array = $get_user_info->getResponseObject()->data->claims;
            $_SESSION['session_in_op'] = $get_tokens_by_code->getResponseIdTokenClaims()->exp[0];
            $_SESSION['user_oxd_id_token'] = $get_tokens_by_code->getResponseIdToken();
            $_SESSION['user_oxd_access_token'] = $get_tokens_by_code->getResponseAccessToken();
            $_SESSION['session_state'] = $_REQUEST['session_state'];
            $_SESSION['state'] = $_REQUEST['state'];
            $get_user_info_array = $get_user_info->getResponseObject()->data->claims;
            $reg_first_name = '';
            $reg_user_name = '';
            $reg_last_name = '';
            $reg_email = '';
            $reg_avatar = '';
            $reg_display_name = '';
            $reg_nikname = '';
            $reg_website = '';
            $reg_middle_name = '';
            $reg_country = '';
            $reg_city = '';
            $reg_region = '';
            $reg_gender = '';
            $reg_postal_code = '';
            $reg_fax = '';
            $reg_home_phone_number = '';
            $reg_phone_mobile_number = '';
            $reg_street_address = '';
            $reg_street_address_2 = '';
            $reg_birthdate = '';
            $reg_user_permission = '';

            if (!empty($get_user_info_array->email[0])) {
                $reg_email = $get_user_info_array->email[0];
            } elseif (!empty($get_tokens_by_code_array->email[0])) {
                $reg_email = $get_tokens_by_code_array->email[0];
            }else{
                echo "<script type='application/javascript'>
					alert('Missing claim : (email). Please talk to your organizational system administrator.');
					location.href='".$base_url."';
				 </script>";
                exit;
            }
            if (!empty($get_user_info_array->website[0])) {
                $reg_website = $get_user_info_array->website[0];
            } elseif (!empty($get_tokens_by_code_array->website[0])) {
                $reg_website = $get_tokens_by_code_array->website[0];
            }
            if (!empty($get_user_info_array->nickname[0])) {
                $reg_nikname = $get_user_info_array->nickname[0];
            } elseif (!empty($get_tokens_by_code_array->nickname[0])) {
                $reg_nikname = $get_tokens_by_code_array->nickname[0];
            }
            if (!empty($get_user_info_array->name[0])) {
                $reg_display_name = $get_user_info_array->name[0];
            } elseif (!empty($get_tokens_by_code_array->name[0])) {
                $reg_display_name = $get_tokens_by_code_array->name[0];
            }
            if (!empty($get_user_info_array->given_name[0])) {
                $reg_first_name = $get_user_info_array->given_name[0];
            } elseif (!empty($get_tokens_by_code_array->given_name[0])) {
                $reg_first_name = $get_tokens_by_code_array->given_name[0];
            }
            if (!empty($get_user_info_array->family_name[0])) {
                $reg_last_name = $get_user_info_array->family_name[0];
            } elseif (!empty($get_tokens_by_code_array->family_name[0])) {
                $reg_last_name = $get_tokens_by_code_array->family_name[0];
            }
            if (!empty($get_user_info_array->middle_name[0])) {
                $reg_middle_name = $get_user_info_array->middle_name[0];
            } elseif (!empty($get_tokens_by_code_array->middle_name[0])) {
                $reg_middle_name = $get_tokens_by_code_array->middle_name[0];
            }

            if (!empty($get_user_info_array->country[0])) {
                $reg_country = $get_user_info_array->country[0];
            } elseif (!empty($get_tokens_by_code_array->country[0])) {
                $reg_country = $get_tokens_by_code_array->country[0];
            }
            if (!empty($get_user_info_array->gender[0])) {
                if ($get_user_info_array->gender[0] == 'male') {
                    $reg_gender = '1';
                } else {
                    $reg_gender = '2';
                }

            } elseif (!empty($get_tokens_by_code_array->gender[0])) {
                if ($get_tokens_by_code_array->gender[0] == 'male') {
                    $reg_gender = '1';
                } else {
                    $reg_gender = '2';
                }
            }
            if (!empty($get_user_info_array->locality[0])) {
                $reg_city = $get_user_info_array->locality[0];
            } elseif (!empty($get_tokens_by_code_array->locality[0])) {
                $reg_city = $get_tokens_by_code_array->locality[0];
            }
            if (!empty($get_user_info_array->postal_code[0])) {
                $reg_postal_code = $get_user_info_array->postal_code[0];
            } elseif (!empty($get_tokens_by_code_array->postal_code[0])) {
                $reg_postal_code = $get_tokens_by_code_array->postal_code[0];
            }
            if (!empty($get_user_info_array->phone_number[0])) {
                $reg_home_phone_number = $get_user_info_array->phone_number[0];
            } elseif (!empty($get_tokens_by_code_array->phone_number[0])) {
                $reg_home_phone_number = $get_tokens_by_code_array->phone_number[0];
            }
            if (!empty($get_user_info_array->phone_mobile_number[0])) {
                $reg_phone_mobile_number = $get_user_info_array->phone_mobile_number[0];
            } elseif (!empty($get_tokens_by_code_array->phone_mobile_number[0])) {
                $reg_phone_mobile_number = $get_tokens_by_code_array->phone_mobile_number[0];
            }
            if (!empty($get_user_info_array->picture[0])) {
                $reg_avatar = $get_user_info_array->picture[0];
            } elseif (!empty($get_tokens_by_code_array->picture[0])) {
                $reg_avatar = $get_tokens_by_code_array->picture[0];
            }
            if (!empty($get_user_info_array->street_address[0])) {
                $reg_street_address = $get_user_info_array->street_address[0];
            } elseif (!empty($get_tokens_by_code_array->street_address[0])) {
                $reg_street_address = $get_tokens_by_code_array->street_address[0];
            }
            if (!empty($get_user_info_array->street_address[1])) {
                $reg_street_address_2 = $get_user_info_array->street_address[1];
            } elseif (!empty($get_tokens_by_code_array->street_address[1])) {
                $reg_street_address_2 = $get_tokens_by_code_array->street_address[1];
            }
            if (!empty($get_user_info_array->birthdate[0])) {
                $reg_birthdate = $get_user_info_array->birthdate[0];
            } elseif (!empty($get_tokens_by_code_array->birthdate[0])) {
                $reg_birthdate = $get_tokens_by_code_array->birthdate[0];
            }
            if (!empty($get_user_info_array->region[0])) {
                $reg_region = $get_user_info_array->region[0];
            } elseif (!empty($get_tokens_by_code_array->region[0])) {
                $reg_region = $get_tokens_by_code_array->region[0];
            }

            $username = '';
            if (!empty($get_user_info_array->user_name[0])) {
                $username = $get_user_info_array->user_name[0];
            } else {
                $email_split = explode("@", $reg_email);
                $username = $email_split[0];
            }
            if(!empty($get_user_info_array->permission[0])){
                $world = str_replace("[","",$get_user_info_array->permission[0]);
                $reg_user_permission = str_replace("]","",$world);
            }elseif(!empty($get_tokens_by_code_array->permission[0])){
                $world = str_replace("[","",$get_user_info_array->permission[0]);
                $reg_user_permission = str_replace("]","",$world);
            }
		        $bool = false;
		        $gluu_new_roles              = json_decode($this->gluu_db_query_select('gluu_new_role'));
		        $gluu_users_can_register    = $this->gluu_db_query_select('gluu_users_can_register');
		        
		        if($gluu_users_can_register == 2 and !empty($gluu_new_roles)){
			        foreach ($gluu_new_roles as $gluu_new_role){
				        if(strstr($reg_user_permission, $gluu_new_role)){
					        $bool = true;
				        }
			        }
			        if(!$bool){
				        echo "<script>
												alert('You are not authorized for an account on this application. If you think this is an error, please contact your OpenID Connect Provider (OP) admin.');
												window.location.href='" . $this->gluu_sso_doing_logout($get_tokens_by_code->getResponseIdToken(), $_REQUEST['session_state'], $_REQUEST['state']) . "';
											 </script>";
				        exit;
			        }
		        }
            
            $auth = $RCMAIL->plugins->exec_hook('authenticate', array(
                'host' => $get_user_info_array->imapHost[0],
                'user' => trim(rcube_utils::get_input_value('_user', $get_user_info_array->imapUsername[0])),
                'pass' => rcube_utils::get_input_value('_pass', $get_user_info_array->imapPassword[0], true,$RCMAIL->config->get('password_charset', 'ISO-8859-1')),
                'cookiecheck' => true,
                'valid'       => true,
            ));

            if($RCMAIL->login($get_user_info_array->imapUsername[0], $get_user_info_array->imapPassword[0],$get_user_info_array->imapHost[0], $auth['cookiecheck'])){
                $RCMAIL->session->remove('temp');
                $RCMAIL->session->regenerate_id(false);
                $RCMAIL->session->set_auth_cookie();
                $RCMAIL->log_login();
                $query = array();
                $redir = $RCMAIL->plugins->exec_hook('login_after', $query + array('_task' => 'mail'));
                unset($redir['abort'], $redir['_err']);
                $query = array('_action' => '');
                $OUTPUT = new rcmail_html_page();
                $redir = $RCMAIL->plugins->exec_hook('login_after', $query + array('_task' => 'mail'));
                $RCMAIL->session->set_auth_cookie();
                $OUTPUT->redirect($redir, 0, true);
            }
            else{
                echo "<script type='application/javascript'>
					alert('Problem with imap connection, please look your imapData in your OpenID provider scopes.');
					location.href='".$base_url."';
				 </script>";
                exit;
            }
        }
    }
    public function gluu_sso_loginform($content)
    {
        $base_url = $this->getBaseUrl();
        $this->include_stylesheet('https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css');
        $oxd_id = $this->gluu_db_query_select('gluu_oxd_id');
        $gluu_send_user_check = $this->gluu_db_query_select('gluu_send_user_check');
        $get_auth_url = $this->get_auth_url();
        $gluu_is_port_working = $this->gluu_is_port_working();
        $this->app->output->add_gui_object('oxd_id', $oxd_id);
        $this->app->output->add_gui_object('base_url', $base_url);
        $this->app->output->add_gui_object('gluu_send_user_check', $gluu_send_user_check);
        $this->app->output->add_gui_object('get_auth_url',$get_auth_url );
        $this->app->output->add_gui_object('gluu_is_port_working',$gluu_is_port_working);
        return $content;
    }
    public function get_auth_url(){
        require_once("GluuOxd_Openid/oxd-rp/Get_authorization_url.php");
        $gluu_config           = json_decode($this->gluu_db_query_select('gluu_config'),true);
        $gluu_auth_type        = $this->gluu_db_query_select('gluu_auth_type');
        $oxd_id = $this->gluu_db_query_select('gluu_oxd_id');
        $get_authorization_url = new Get_authorization_url();
        $get_authorization_url->setRequestOxdId($oxd_id);
        $get_authorization_url->setRequestScope($gluu_config['config_scopes']);
        if($gluu_auth_type != "default"){
            $get_authorization_url->setRequestAcrValues([$gluu_auth_type]);
        }else{
            $get_authorization_url->setRequestAcrValues(null);
        }
        $get_authorization_url->request();

        return $get_authorization_url->getResponseAuthorizationUrl();
    }
    public function gluu_is_port_working(){
        $config_option = json_decode($this->gluu_db_query_select('gluu_config'),true);
        $connection = @fsockopen('127.0.0.1', $config_option['gluu_oxd_port']);
        if (is_resource($connection))
        {
            fclose($connection);
            return true;
        }
        else
        {
            return false;
        }
    }
    public function gluu_is_oxd_registered(){
        if($this->gluu_db_query_select('gluu_oxd_id')){
            $oxd_id = $this->gluu_db_query_select('gluu_oxd_id');
            if(!$oxd_id ) {
                return 0;
            } else {
                return $oxd_id;
            }
        }
    }
    public function gluu_db_query_select($gluu_action){
        return self::$gluuDB->query("SELECT `gluu_value` FROM `gluu_table` WHERE `gluu_action` LIKE '".$gluu_action."'")->fetchAll(PDO::FETCH_COLUMN, 0)[0];
    }
    public function gluu_db_query_insert($gluu_action, $gluu_value){
        return self::$gluuDB->query("INSERT INTO gluu_table (gluu_action, gluu_value) VALUES ('".$gluu_action."', '".$gluu_value."')");
    }
    public function gluu_db_query_update($gluu_action, $gluu_value){
        self::$gluuDB->query("UPDATE `gluu_table` SET `gluu_value` = '".$gluu_value."' WHERE `gluu_action` LIKE '".$gluu_action."';");
        return self::$gluuDB->query("SELECT `gluu_value` FROM `gluu_table` WHERE `gluu_action` LIKE '".$gluu_action."'")->fetchAll(PDO::FETCH_COLUMN, 0)[0];
    }

    public function getBaseUrl()
    {
        $currentPath = $_SERVER['PHP_SELF'];
        $pathInfo = pathinfo($currentPath);
        $hostName = $_SERVER['HTTP_HOST'];
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        if (strpos($pathInfo['dirname'], '\\') !== false) {
            return $protocol . $hostName . "/";
        } else {
            return $protocol . $hostName . $pathInfo['dirname'] . "/";
        }
    }
    /**
     * Doing logout is something is wrong
     */
    public function gluu_sso_doing_logout($user_oxd_id_token, $session_states, $state)
    {
        @session_start();

        require_once("GluuOxd_Openid/oxd-rp/Logout.php");
        $RCMAIL = rcmail::get_instance($GLOBALS['env']);
        $oxd_id =  $this->gluu_db_query_select("gluu_oxd_id");
        $gluu_provider = $this->gluu_db_query_select('gluu_provider');
        $gluu_config = json_decode($this->gluu_db_query_select("gluu_config"),true);
        $arrContextOptions=array(
          "ssl"=>array(
            "verify_peer"=>false,
            "verify_peer_name"=>false,
          ),
        );
        $json = file_get_contents($gluu_provider.'/.well-known/openid-configuration', false, stream_context_create($arrContextOptions));
        $obj = json_decode($json);
        if (!empty($obj->end_session_endpoint ) or $gluu_provider == 'https://accounts.google.com') {
            if (!empty($_SESSION['user_oxd_id_token'])) {
                if ($oxd_id && $_SESSION['user_oxd_id_token'] && $_SESSION['session_in_op']) {
                    $logout = new Logout();
                    $logout->setRequestOxdId($oxd_id);
                    $logout->setRequestIdToken($_SESSION['user_oxd_id_token']);
                    $logout->setRequestPostLogoutRedirectUri($gluu_config['post_logout_redirect_uri']);
                    $logout->setRequestSessionState($_SESSION['session_state']);
                    $logout->setRequestState($_SESSION['state']);
                    $logout->request();
                    unset($_SESSION['user_oxd_access_token']);
                    unset($_SESSION['user_oxd_id_token']);
                    unset($_SESSION['session_state']);
                    unset($_SESSION['state']);
                    unset($_SESSION['session_in_op']);
                    return $logout->getResponseObject()->data->uri;
                }
            }
        }

        return getBaseUrl();
    }
}
