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
    /*
     * Gluu SSO admin page configuration.
    */
    public function admin_html()
    {
        $base_url  = @( $_SERVER["HTTPS"] != 'on' ) ? 'http://'.$_SERVER["SERVER_NAME"] :  'https://'.$_SERVER["SERVER_NAME"];
        $RCMAIL = rcmail::get_instance($GLOBALS['env']);
        $db = $RCMAIL->db;

        $query = "CREATE TABLE IF NOT EXISTS `gluu_table` (

              `gluu_action` varchar(255) NOT NULL,
              `gluu_value` longtext NOT NULL,
              UNIQUE(`gluu_action`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
        $result = $db->query($query);
        if($db->query("SELECT `gluu_value` FROM `gluu_table` WHERE `gluu_action` LIKE 'scopes'")){
            $get_scopes = json_encode(array("openid","profile","email","address","clientinfo","mobile_phone","phone"));
            $result = $db->query("INSERT INTO gluu_table (gluu_action, gluu_value) VALUES ('scopes','$get_scopes')");
        }
        if($db->query("SELECT `gluu_value` FROM `gluu_table` WHERE `gluu_action` LIKE 'custom_scripts'")){
            $custom_scripts = json_encode(array(
                array('name'=>'Google','image'=>'plugins/gluu_sso/GluuOxd_Openid/images/icons/google.png','value'=>'gplus'),
                array('name'=>'Basic','image'=>'plugins/gluu_sso/GluuOxd_Openid/images/icons/basic.png','value'=>'basic'),
                array('name'=>'Duo','image'=>'plugins/gluu_sso/GluuOxd_Openid/images/icons/duo.png','value'=>'duo'),
                array('name'=>'U2F token','image'=>'plugins/gluu_sso/GluuOxd_Openid/images/icons/u2f.png','value'=>'u2f')
            ));
            $result = $db->query("INSERT INTO gluu_table (gluu_action, gluu_value) VALUES ('custom_scripts','$custom_scripts')");
        }
        if($db->query("SELECT `gluu_value` FROM `gluu_table` WHERE `gluu_action` LIKE 'oxd_config'")){
            $oxd_config = json_encode(array(
                "oxd_host_ip" => '127.0.0.1',
                "oxd_host_port" =>8099,
                "admin_email" => '',
                "authorization_redirect_uri" => $base_url.'?_action=plugin.gluu_sso-login-from-gluu',
                "logout_redirect_uri" => $base_url.'?_action=plugin.gluu_sso-login-from-gluu',
                "scope" => ["openid","profile","email","address","clientinfo","mobile_phone","phone"],
                "grant_types" =>["authorization_code"],
                "response_types" => ["code"],
                "application_type" => "web",
                "redirect_uris" => [ $base_url.'?_action=plugin.gluu_sso-login-from-gluu' ],
                "acr_values" => [],
            ));
            $result = $db->query("INSERT INTO gluu_table (gluu_action, gluu_value) VALUES ('oxd_config','$oxd_config')");
        }
        if($db->query("SELECT `gluu_value` FROM `gluu_table` WHERE `gluu_action` LIKE 'iconSpace'")){
            $iconSpace = '10';
            $db->query("INSERT INTO gluu_table (gluu_action, gluu_value) VALUES ('iconSpace','$iconSpace')");
        }
        if($db->query("SELECT `gluu_value` FROM `gluu_table` WHERE `gluu_action` LIKE 'iconCustomSize'")){
            $iconCustomSize = '50';
            $db->query("INSERT INTO gluu_table (gluu_action, gluu_value) VALUES ('iconCustomSize','$iconCustomSize')");
        }
        if($db->query("SELECT `gluu_value` FROM `gluu_table` WHERE `gluu_action` LIKE 'iconCustomWidth'")){
            $iconCustomWidth = '200';
            $db->query("INSERT INTO gluu_table (gluu_action, gluu_value) VALUES ('iconCustomWidth','$iconCustomWidth')");
        }
        if($db->query("SELECT `gluu_value` FROM `gluu_table` WHERE `gluu_action` LIKE 'iconCustomHeight'")){
            $iconCustomHeight = '35';
            $db->query("INSERT INTO gluu_table (gluu_action, gluu_value) VALUES ('iconCustomWidth','$iconCustomHeight')");
        }
        if($db->query("SELECT `gluu_value` FROM `gluu_table` WHERE `gluu_action` LIKE 'loginCustomTheme'")){
            $loginCustomTheme = 'default';
            $db->query("INSERT INTO gluu_table (gluu_action, gluu_value) VALUES ('loginCustomTheme','$loginCustomTheme')");
        }
        if($db->query("SELECT `gluu_value` FROM `gluu_table` WHERE `gluu_action` LIKE 'loginTheme'")){
            $loginTheme = 'circle';
            $db->query("INSERT INTO gluu_table (gluu_action, gluu_value) VALUES ('loginTheme','$loginTheme')");
        }
        if($db->query("SELECT `gluu_value` FROM `gluu_table` WHERE `gluu_action` LIKE 'iconCustomColor'")){
            $iconCustomColor = '#0000FF';
            $db->query("INSERT INTO gluu_table (gluu_action, gluu_value) VALUES ('iconCustomColor','$iconCustomColor')");
        }

        $get_scopes =   json_decode($db->query("SELECT `gluu_value` FROM `gluu_table` WHERE `gluu_action` LIKE 'scopes'")->fetchAll(PDO::FETCH_COLUMN, 0)[0],true);
        $oxd_config =   json_decode($db->query("SELECT `gluu_value` FROM `gluu_table` WHERE `gluu_action` LIKE 'oxd_config'")->fetchAll(PDO::FETCH_COLUMN, 0)[0],true);
        $custom_scripts = json_decode($db->query("SELECT `gluu_value` FROM `gluu_table` WHERE `gluu_action` LIKE 'custom_scripts'")->fetchAll(PDO::FETCH_COLUMN, 0)[0],true);
        $iconSpace =                  $db->query("SELECT `gluu_value` FROM `gluu_table` WHERE `gluu_action` LIKE 'iconSpace'")->fetchAll(PDO::FETCH_COLUMN, 0)[0];
        $iconCustomSize =             $db->query("SELECT `gluu_value` FROM `gluu_table` WHERE `gluu_action` LIKE 'iconCustomSize'")->fetchAll(PDO::FETCH_COLUMN, 0)[0];
        $iconCustomWidth =            $db->query("SELECT `gluu_value` FROM `gluu_table` WHERE `gluu_action` LIKE 'iconCustomWidth'")->fetchAll(PDO::FETCH_COLUMN, 0)[0];
        $iconCustomHeight =           $db->query("SELECT `gluu_value` FROM `gluu_table` WHERE `gluu_action` LIKE 'iconCustomHeight'")->fetchAll(PDO::FETCH_COLUMN, 0)[0];
        $loginCustomTheme =           $db->query("SELECT `gluu_value` FROM `gluu_table` WHERE `gluu_action` LIKE 'loginCustomTheme'")->fetchAll(PDO::FETCH_COLUMN, 0)[0];
        $loginTheme =                 $db->query("SELECT `gluu_value` FROM `gluu_table` WHERE `gluu_action` LIKE 'loginTheme'")->fetchAll(PDO::FETCH_COLUMN, 0)[0];
        $iconCustomColor =            $db->query("SELECT `gluu_value` FROM `gluu_table` WHERE `gluu_action` LIKE 'iconCustomColor'")->fetchAll(PDO::FETCH_COLUMN, 0)[0];

        $oxd_id = '';
        if($db->query("SELECT `gluu_value` FROM `gluu_table` WHERE `gluu_action` LIKE 'oxd_id'")->fetchAll(PDO::FETCH_COLUMN, 0)[0]){
            $oxd_id = $db->query("SELECT `gluu_value` FROM `gluu_table` WHERE `gluu_action` LIKE 'oxd_id'")->fetchAll(PDO::FETCH_COLUMN, 0)[0];
        }


        $html = '';
        $html_cus='';
        foreach($custom_scripts as $custom_script) {
            $html_cus .= 'if (document.getElementById("' . $custom_script['value'] . '_enable").checked) {
            flag = 1;
            if (document.getElementById(\'gluuoxd_openid_login_default_radio\').checked && !document.getElementById(\'iconwithtext\').checked)
                jQuery("#gluuox_login_icon_preview_' . $custom_script['value'] . '").show();
            if (document.getElementById(\'gluuoxd_openid_login_custom_radio\').checked && !document.getElementById(\'iconwithtext\').checked)
                jQuery("#gluuOx_custom_login_icon_preview_' . $custom_script['value'] . '").show();
            if (document.getElementById(\'gluuoxd_openid_login_default_radio\').checked && document.getElementById(\'iconwithtext\').checked)
                jQuery("#gluuox_login_button_preview_' . $custom_script['value'] . '").show();
            if (document.getElementById(\'gluuoxd_openid_login_custom_radio\').checked && document.getElementById(\'iconwithtext\').checked)
                jQuery("#gluuOx_custom_login_button_preview_' . $custom_script['value'] . '").show();
        }
        else if (!document.getElementById("' . $custom_script['value'] . '_enable").checked) {
            jQuery("#gluuox_login_icon_preview_'.$custom_script['value'].'").hide();
            jQuery("#gluuOx_custom_login_icon_preview_'.$custom_script['value'].'").hide();
            jQuery("#gluuox_login_button_preview_'.$custom_script['value'].'").hide();
            jQuery("#gluuOx_custom_login_button_preview_'.$custom_script['value'].'").hide();
        }';
        }
        $html.='
<script>
    var $m = jQuery.noConflict();
    var $ = jQuery;
    $m(document).ready(function () {
        $oxd_id = "'.$oxd_id.'";
        if ($oxd_id) {
            voiddisplay("#socialsharing");
            setactive(\'social-sharing-setup\');
        } else {
            setactive(\'account_setup\');
        }
        $m(".navbar a").click(function () {
            $id = $m(this).parent().attr(\'id\');
            setactive($id);
            $href = $m(this).data(\'method\');
            voiddisplay($href);
        });

        $m(\'#error-cancel\').click(function () {
            $error = "";
            $m(".error-msg").css("display", "none");
        });
        $m(\'#success-cancel\').click(function () {
            $success = "";
            $m(".success-msg").css("display", "none");
        });

        $m(".test").click(function () {
            $m(".mo2f_thumbnail").hide();
            $m("#twofactorselect").show();
            $m("#test_2factor").val($m(this).data("method"));
            $m("#mo2f_2factor_test_form").submit();
        });
    });
    function setactive($id) {
        $m(".navbar-tabs>li").removeClass("active");
        $m("#minisupport").show();
        $id = \'#\' + $id;
        $m($id).addClass("active");
    }
    function voiddisplay($href) {
        $m(".page").css("display", "none");
        $m($href).css("display", "block");
    }
    function mo2f_valid(f) {
        !(/^[a-zA-Z?,.\(\)\/@ 0-9]*$/).test(f.value) ? f.value = f.value.replace(/[^a-zA-Z?,.\(\)\/@ 0-9]/, "") : null;
    }
    jQuery(document).ready(function () {

        var tempHorSize = "'.$iconCustomSize.'";
        var tempHorTheme = "'.$loginTheme.'";
        var tempHorCustomTheme = "'.$loginCustomTheme.'";
        var tempHorCustomColor = "'.$iconCustomColor.'";
        var tempHorSpace = "'.$iconSpace.'";
        var tempHorHeight = "'.$iconCustomHeight.'";

        gluuOxLoginPreview(setSizeOfIcons(), tempHorTheme, tempHorCustomTheme, tempHorCustomColor, tempHorSpace, tempHorHeight);
        checkLoginButton();

    });
    function setLoginTheme() {
        return jQuery(\'input[name=gluuoxd_openid_login_theme]:checked\', \'#form-apps\').val();
    }
    function setLoginCustomTheme() {
        return jQuery(\'input[name=gluuoxd_openid_login_custom_theme]:checked\', \'#form-apps\').val();
    }
    function setSizeOfIcons() {
        if ((jQuery(\'input[name=gluuoxd_openid_login_theme]:checked\', \'#form-apps\').val()) == \'longbutton\') {
            return document.getElementById(\'gluuox_login_icon_width\').value;
        } else {
            return document.getElementById(\'gluuox_login_icon_size\').value;
        }
    }
    function gluuOxLoginPreview(t, r, l, p, n, h) {

        if (l == \'default\') {
            if (r == \'longbutton\') {
                var a = "btn-defaulttheme";
                jQuery("." + a).css("width", t + "px");
                if (h > 26) {
                    jQuery("." + a).css("height", "26px");
                    jQuery("." + a).css("padding-top", (h - 26) / 2 + "px");
                    jQuery("." + a).css("padding-bottom", (h - 26) / 2 + "px");
                } else {
                    jQuery("." + a).css("height", h + "px");
                    jQuery("." + a).css("padding-top", (h - 26) / 2 + "px");
                    jQuery("." + a).css("padding-bottom", (h - 26) / 2 + "px");
                }
                jQuery(".fa").css("padding-top", (h - 35) + "px");
                jQuery("." + a).css("margin-bottom", n + "px");
            } else {
                var a = "gluuox_login_icon_preview";
                jQuery("." + a).css("margin-left", n + "px");
                if (r == "circle") {
                    jQuery("." + a).css({height: t, width: t});
                    jQuery("." + a).css("borderRadius", "999px");
                } else if (r == "oval") {
                    jQuery("." + a).css("borderRadius", "5px");
                    jQuery("." + a).css({height: t, width: t});
                } else if (r == "square") {
                    jQuery("." + a).css("borderRadius", "0px");
                    jQuery("." + a).css({height: t, width: t});
                }
            }
        }
        else if (l == \'custom\') {
            if (r == \'longbutton\') {
                var a = "btn-customtheme";
                jQuery("." + a).css("width", (t) + "px");
                if (h > 26) {
                    jQuery("." + a).css("height", "26px");
                    jQuery("." + a).css("padding-top", (h - 26) / 2 + "px");
                    jQuery("." + a).css("padding-bottom", (h - 26) / 2 + "px");
                } else {
                    jQuery("." + a).css("height", h + "px");
                    jQuery("." + a).css("padding-top", (h - 26) / 2 + "px");
                    jQuery("." + a).css("padding-bottom", (h - 26) / 2 + "px");
                }
                jQuery("." + a).css("margin-bottom", n + "px");
                jQuery("." + a).css("background", p);
            } else {
                var a = "gluuOx_custom_login_icon_preview";
                jQuery("." + a).css({height: t - 8, width: t});
                jQuery("." + a).css("padding-top", "8px");
                jQuery("." + a).css("margin-left", n + "px");
                jQuery("." + a).css("background", p);

                if (r == "circle") {
                    jQuery("." + a).css("borderRadius", "999px");
                } else if (r == "oval") {
                    jQuery("." + a).css("borderRadius", "5px");
                } else if (r == "square") {
                    jQuery("." + a).css("borderRadius", "0px");
                }
                jQuery("." + a).css("font-size", (t - 16) + "px");
            }
        }
        previewLoginIcons();
    }
    function checkLoginButton() {
        if (document.getElementById(\'iconwithtext\').checked) {
            if (setLoginCustomTheme() == \'default\') {
                jQuery(".gluuox_login_icon_preview").hide();
                jQuery(".gluuOx_custom_login_icon_preview").hide();
                jQuery(".btn-customtheme").hide();
                jQuery(".btn-defaulttheme").show();
            } else if (setLoginCustomTheme() == \'custom\') {
                jQuery(".gluuox_login_icon_preview").hide();
                jQuery(".gluuOx_custom_login_icon_preview").hide();
                jQuery(".btn-defaulttheme").hide();
                jQuery(".btn-customtheme").show();
            }
            jQuery("#commontheme").hide();
            jQuery(".longbuttontheme").show();
        }
        else {
            if (setLoginCustomTheme() == \'default\') {
                jQuery(".gluuox_login_icon_preview").show();
                jQuery(".btn-defaulttheme").hide();
                jQuery(".btn-customtheme").hide();
                jQuery(".gluuOx_custom_login_icon_preview").hide();
            } else if (setLoginCustomTheme() == \'custom\') {
                jQuery(".gluuox_login_icon_preview").hide();
                jQuery(".gluuOx_custom_login_icon_preview").show();
                jQuery(".btn-defaulttheme").hide();
                jQuery(".btn-customtheme").hide();
            }
            jQuery("#commontheme").show();
            jQuery(".longbuttontheme").hide();
        }

        previewLoginIcons();
    }
    function previewLoginIcons() {
        var flag = 0;'.$html_cus.'
    if (flag) {
            jQuery("#no_apps_text").hide();
        }else{
            jQuery("#no_apps_text").show();
        }}
    var selectedApps = [];
    function setTheme() {
        return jQuery(\'input[name=gluuoxd_openid_share_theme]:checked\', \'#settings_form\').val();
    }
    function setCustomTheme() {
        return jQuery(\'input[name=gluuoxd_openid_share_custom_theme]:checked\', \'#settings_form\').val();
    }
    function gluuOxLoginSizeValidate(e) {
        var t = parseInt(e.value.trim());
        t > 60 ? e.value = 60 : 20 > t && (e.value = 20);
        reloadLoginPreview();
    }
    function gluuOxLoginSpaceValidate(e) {
        var t = parseInt(e.value.trim());
        t > 60 ? e.value = 60 : 0 > t && (e.value = 0);
        reloadLoginPreview();
    }
    function gluuOxLoginWidthValidate(e) {
        var t = parseInt(e.value.trim());
        t > 1000 ? e.value = 1000 : 140 > t && (e.value = 140)
        reloadLoginPreview();
    }
    function gluuOxLoginHeightValidate(e) {
        var t = parseInt(e.value.trim());
        t > 100 ? e.value = 100 : 10 > t && (e.value = 10)
        reloadLoginPreview();
    }
    function reloadLoginPreview() {
        if (setLoginTheme() == \'longbutton\')
            gluuOxLoginPreview(document.getElementById(\'gluuox_login_icon_width\').value, setLoginTheme(), setLoginCustomTheme(), document.getElementById(\'gluuox_login_icon_custom_color\').value, document.getElementById(\'gluuox_login_icon_space\').value,
                document.getElementById(\'gluuox_login_icon_height\').value);
        else
            gluuOxLoginPreview(document.getElementById(\'gluuox_login_icon_size\').value, setLoginTheme(), setLoginCustomTheme(), document.getElementById(\'gluuox_login_icon_custom_color\').value, document.getElementById(\'gluuox_login_icon_space\').value);
    }
</script>
<div class="mo2f_container">
    <div class="container">
        <div id="messages">';
        if (!empty($_SESSION['message_error'])) {
            $html .= '<div class="mess_red_error">' . $_SESSION['message_error'] . '
                </div>';
            unset($_SESSION['message_error']);
        }
        if (!empty($_SESSION['message_success'])) {
            $html.='<div class="mess_green">'.$_SESSION['message_success'].'
                </div>';
            unset($_SESSION['message_success']);
        }
        $html.='</div>
        <ul class="navbar navbar-tabs">
            <li id="account_setup"><a data-method="#accountsetup">General</a></li>
            <li id="social-sharing-setup"><a data-method="#socialsharing">OpenID Connect Configuration</a></li>
            <li id="social-login-setup"><a data-method="#sociallogin">RoundCube Configuration</a></li>
            <li id="help_trouble"><a data-method="#helptrouble">Help & Troubleshooting</a></li>
        </ul>
        <div class="container-page">';
        if (!$oxd_id) {
            $html.='<div class="page" id="accountsetup">
                    <div class="mo2f_table_layout">
                        <form id="register_GluuOxd" name="f" method="post"
                              action="?_task=settings&_action=plugin.gluu_sso-save">
                            <input type="hidden" name="form_key" value="general_register_page"/>
                            <div class="login_GluuOxd">
                                <div class="mess_red">
                                    Please enter the details of your OpenID Connect Provider.
                                </div>
                                <br/>
                                <div><h3>Register your site with an OpenID Connect Provider</h3></div>
                                <hr>
                                <div class="mess_red">If you do not have an OpenID Connect provider, you may want to look at the Gluu Server (
                                    <a target="_blank" href="http://www.gluu.org/docs">Like RoundCube, there is a free open source Community Edition. For more information about Gluu Server support please visit <a target="_blank" href="http://www.gluu.org">our website.</a></a>)
                                </div>
                                <div class="mess_red">
                                    <h3>Instructions to Install oxd server</h3>
                                    <br><b>NOTE:</b> The oxd server should be installed on the same server as your RoundCube site. It is recommended that the oxd server listen only on the localhost interface, so only your local applications can reach its API"s.
                                    <ol style="list-style:decimal !important; margin: 30px">
                                        <li>Extract and copy in your DMZ Server.</li>
                                        <li>Download the latest oxd-server package for Centos or Ubuntu. See
                                            <a target="_blank" href="http://gluu.org/docs-oxd">oxd docs</a> for more info.
                                        </li><li>If you are installing an .rpm or .deb, make sure you have Java in your server.
                                        </li><li>Edit <b>oxd-conf.json</b> in the <b>conf</b> directory to specify the port on which
                                            it will run, and specify the hostname of the OpenID Connect provider.</li>
                                        <li>Open the command line and navigate to the extracted folder in the <b>bin</b> directory.</li>
                                        <li>For Linux environment, run <b>sh oxd-start.sh &amp;</b></li>
                                        <li>For Windows environment, run <b>oxd-start.bat</b></li>
                                        <li>After the server starts, set the port number and your email in this page and click Next.</li>
                                    </ol>
                                </div>
                                <hr>
                                <div>
                                    <table class="table">
                                        <tr>
                                            <td><b><font color="#FF0000">*</font>Admin Email:</b></td>
                                            <td><input class="" type="email" name="loginemail" id="loginemail"
                                                       autofocus="true" required placeholder="person@example.com"
                                                       style="width:400px;"
                                                       value="'.$oxd_config['admin_email'].'"/>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><b><font color="#FF0000">*</font>Port number:</b></td>
                                            <td>
                                                <input class="" type="number" name="oxd_port" min="0" max="65535"
                                                       value="'.$oxd_config['oxd_host_port'].'"
                                                       style="width:400px;" placeholder="Enter port number."/>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                                <br/>
                                <div><input type="submit" name="submit" value="Next" style="width: 120px" class=""/></div>
                                <br/>
                                <br/>
                            </div>
                        </form>
                    </div>
                </div>';
        } else{
            $html.='<div class="page" id="accountsetup">
                    <div>
                        <div>
                            <div class="about">
                                <h3 style="color: #45a8ff" class="sc"><img style=" height: 45px; margin-left: 20px;" src="plugins/gluu_sso/GluuOxd_Openid/images/icons/ox.png"/>&nbsp; server config</h3>
                            </div>
                        </div>
                        <div class="entry-edit" >
                            <div class="entry-edit-head">
                                <h4 class="icon-head head-edit-form fieldset-legend">OXD id</h4>
                            </div>
                            <div class="fieldset">
                                <div class="hor-scroll">
                                    <table class="form-list container">
                                        <tr class="wrapper-trr">
                                            <td class="value">
                                                <input style="width: 500px !important;" type="text" name="oxd_id" value="'.$oxd_id.'" disabled/>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <form action="?_task=settings&_action=plugin.gluu_sso-save" method="post">
                        <input type="hidden" name="form_key" value="general_oxd_id_reset"/>
                        <p><input style="width: 200px; background-color: red !important; cursor: pointer" type="submit" class="button button-primary " value="Reset configurations" name="resetButton"/></p>
                    </form>
                </div>';
        }

        $html.='<div class="page" id="socialsharing">';
        if (!$oxd_id){
            $html.='<div class="mess_red">
                        Please enter OXD configuration to continue.
                    </div><br/>';
        }
        $html.='<div>
                    <form action="?_task=settings&_action=plugin.gluu_sso-save" method="post"
                          enctype="multipart/form-data">
                        <input type="hidden" name="form_key" value="openid_config_page"/>
                        <div>
                            <div>
                                <div class="about">
                                    <br/>
                                    <h3 style="color: #00aa00" class="sc"><img style="height: 45px; margin-left: 30px;" src="plugins/gluu_sso/GluuOxd_Openid/images/icons/gl.png"/> &nbsp; server config
                                    </h3>
                                </div>
                            </div>
                            <div class="entry-edit" >
                                <div class="entry-edit-head" style="background-color: #00aa00 !important;">
                                    <h4 class="icon-head head-edit-form fieldset-legend">All Scopes</h4>
                                </div>
                                <div class="fieldset">
                                    <div class="hor-scroll">
                                        <table class="form-list">
                                            <tr class="wrapper-trr">';
        foreach ($get_scopes as $scop){
            $html.='<td class="value">';
            if ($scop == 'openid'){
                $html.='<input ';
                if (!$oxd_id) $html.=' disabled ';
                $html.='type="hidden"  name="scope[]"  ';
                if ($oxd_config && in_array($scop, $oxd_config['scope'])) {
                    $html.=' checked ';
                }
                $html.='value="'.$scop.'" />';
            }
            $html.='<input ';
            if (!$oxd_id) $html.=' disabled ';
            $html.=' type="checkbox"  name="scope[]" ';
            if ($oxd_config && in_array($scop, $oxd_config['scope'])) {
                $html.=' checked '; }   $html.='id="'.$scop.'" value="'.$scop.'" ';
            if ($scop == 'openid') $html.= ' disabled   />';
            $html.= '<label for="'.$scop.'">'.$scop.'</label>
                                                    </td>';
        }
        $html.= '</tr>
        </table>
        <table class="form-list" style="text-align: center">
                <tr class="wrapper-tr" style="text-align: center">
                    <th style="border: 1px solid #43ffdf; width: 70px;text-align: center"><h3>N</h3></th>
                    <th style="border: 1px solid #43ffdf;width: 200px;text-align: center"><h3>Name</h3></th>
                    <th style="border: 1px solid #43ffdf;width: 200px;text-align: center"><h3>Delete</h3></th>
                </tr>';
        $n = 0;
        foreach ($get_scopes as $scop) {
            $n++;
            $html.= '<tr class="wrapper-trr">
                                                    <td style="border: 1px solid #43ffdf; padding: 0px; width: 70px"><h3>'.$n.'</h3></td>
                                                    <td style="border: 1px solid #43ffdf; padding: 0px; width: 200px"><h3><label for="'.$scop.'">'.$scop.'</label></h3></td>
                                                    <td style="border: 1px solid #43ffdf; padding: 0px; width: 200px">';
            if ($n == 1){
                $html.= '<form></form>';

            }

            $html.= '<form
                                                            action="?_task=settings&_action=plugin.gluu_sso-save"
                                                            method="post">
                                                            <input type="hidden" name="form_key"
                                                                   value="openid_config_delete_scop"/>
                                                            <input type="hidden"
                                                                   value="'.$scop.'"
                                                                   name="value_scope"/>';
            if ($scop != 'openid'){
                $html.= '<input  style="width: 100px; background-color: red !important; cursor: pointer"
                                                                        type="submit"
                                                                        class="button button-primary "';
                if (!$oxd_id) $html.= 'disabled';
                $html.= ' value="Delete" name="delete_scop"/>';
            }
            $html.= '</form>
                                                    </td>
                                                </tr>';
        }
        $html.= '</table>
                                    </div>
                                </div>
                            </div>
                            <div class="entry-edit">
                                <div class="entry-edit-head" style="background-color: #00aa00 !important;">
                                    <h4 class="icon-head head-edit-form fieldset-legend">Add scopes</h4>
                                </div>
                                <div class="fieldset">
                                    <input type="button" id="adding" class="button button-primary button-large add" style="width: 100px" value="Add scopes"/>
                                    <div class="hor-scroll">
                                        <table class="form-list5 container">
                                            <tr class="wrapper-tr">
                                                <td class="value">
                                                    <input type="text" placeholder="Input scope name" name="scope_name[]"/>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="entry-edit" >
                                <div class="entry-edit-head" style="background-color: #00aa00 !important;">
                                    <h4 class="icon-head head-edit-form fieldset-legend">All custom scripts</h4>
                                </div>
                                <div class="fieldset">
                                    <div class="hor-scroll">
                                        <h3>Manage Authentication</h3>
                                        <p>An OpenID Connect Provider (OP) like the Gluu Server may provide many different work flows for
                                            authentication. For example, an OP may offer password authentication, token authentication, social
                                            authentication, biometric authentication, and other different mechanisms. Offering a lot of different
                                            types of authentication enables an OP to offer the most convenient, secure, and affordable option to
                                            identify a person, depending on the need to mitigate risk, and the sensors and inputs available on the
                                            device that the person is using.
                                        </p>
                                        <p>
                                            The OP enables a client (like a RoundCube site), to signal which type of authentication should be
                                            used. The client can register a
                                            <a target="_blank" href="http://openid.net/specs/openid-connect-registration-1_0.html#ClientMetadata">default_acr_value</a>
                                            or during the authentication process, a client may request a specific type of authentication using the
                                            <a target="_blank" href="http://openid.net/specs/openid-connect-core-1_0.html#AuthRequest">acr_values</a> parameter.
                                            This is the mechanism that the Gluu SSO module uses: each login icon corresponds to a acr request value.
                                            For example, and acr may tell the OpenID Connect to use Facebook, Google or even plain old password authentication.
                                            The nice thing about this approach is that your applications (like RoundCube) don"t have
                                            to implement the business logic for social login--it"s handled by the OpenID Connect Provider.
                                        </p>
                                        <p>';
        $html.= 'If you are using the Gluu Server as your OP, youll notice that in the Manage Custom Scripts
                                            tab of oxTrust (the Gluu Server admin interface), each authentication script has a name.
                                            This name corresponds to the acr value.  The default acr for password authentication is set in
                                            the
                                            <a target="_blank" href="https://www.gluu.org/docs/admin-guide/configuration/#manage-authentication">LDAP Authentication</a>,
                                            section--look for the "Name" field. Likewise, each custom script has a "Name", for example see the
                                            <a target="_blank" href="https://www.gluu.org/docs/admin-guide/configuration/#manage-custom-scripts">Manage Custom Scripts</a> section.
                                        </p>
                                        <table style="width:100%;display: table;">
                                            <tbody>
                                            <tr>';

        foreach ($custom_scripts as $custom_script) {
            $html.= '
                                                    <td style="width:25%">
                                                        <input type="checkbox"';
            if (!$oxd_id) $html.= 'disabled';
            $html.= 'id="'.$custom_script['value'].'_enable"
                                                               class="app_enable"
                                                               name="gluuoxd_openid_'.$custom_script['value'].'_enable"
                                                               value="1"
                                                               onchange="previewLoginIcons();"  ';

            if ($db->query("SELECT `gluu_value` FROM `gluu_table` WHERE `gluu_action` LIKE '".$custom_script['value']."Enable'")->fetchAll(PDO::FETCH_COLUMN, 0)[0]) $html.= 'checked';
            $html.= '/><b>'.$custom_script['name'].'</b>
                                                    </td>';

        }
        $html.= '</tr>
                                            <tr style="display: none;">
                                            </tr>
                                            </tbody>
                                        </table>
                                        <table class="form-list" style="text-align: center">
                                            <tr class="wrapper-tr" style="text-align: center">
                                                <th style="border: 1px solid #43ffdf; width: 70px;text-align: center"><h3>N</h3></th>
                                                <th style="border: 1px solid #43ffdf;width: 200px;text-align: center"><h3>Display Name</h3></th>
                                                <th style="border: 1px solid #43ffdf;width: 200px;text-align: center"><h3>ACR Value</h3></th>
                                                <th style="border: 1px solid #43ffdf;width: 200px;text-align: center"><h3>Image</h3></th>
                                                <th style="border: 1px solid #43ffdf;width: 200px;text-align: center"><h3>Delete</h3></th>
                                            </tr>
                                            ';
        $n = 0;
        foreach ($custom_scripts as $custom_script) {
            $n++;
            $html.= '<tr class="wrapper-trr">
                                                    <td style="border: 1px solid #43ffdf; padding: 0px; width: 70px"><h3>'.$n.'</h3></td>
                                                    <td style="border: 1px solid #43ffdf; padding: 0px; width: 200px"><h3>'.$custom_script['name'].'</h3></td>
                                                    <td style="border: 1px solid #43ffdf; padding: 0px; width: 200px"><h3>'.$custom_script['value'].'</h3></td>
                                                    <td style="border: 1px solid #43ffdf; padding: 0px; width: 200px"><img src="'.$custom_script['image'].'" width="40px" height="40px"/></td>
                                                    <td style="border: 1px solid #43ffdf; padding: 0px; width: 200px">';
            if ($n == 1){
                $html.= '<form></form>';

            }
            $html.= '<form
                                                            action="?_task=settings&_action=plugin.gluu_sso-save"
                                                            method="post">
                                                            <input type="hidden" name="form_key"
                                                                   value="openid_config_delete_custom_scripts"/>
                                                            <input type="hidden"
                                                                   value="'.$custom_script['value'].'"
                                                                   name="value_script"/>
                                                            <input
                                                                style="width: 100px; background-color: red !important; cursor: pointer"
                                                                type="submit"
                                                                class="button button-primary " '; if (!$oxd_id)  {$html.= 'disabled';}
            $html.= 'value="Delete" name="delete_config"/>
                                                        </form>
                                                    </td>
                                                </tr>';

        }
        $html.= '</table>
                                    </div>
                                </div>
                                <br/>
                                <div class="entry-edit-head" style="background-color: #00aa00 !important;">
                                    <h4 class="icon-head head-edit-form fieldset-legend">Add multiple custom scripts</h4>
                                    <p style="color:#cc0b07; font-style: italic; font-weight: bold;font-size: larger"> Both fields are required</p>
                                </div>
                                <div class="fieldset">
                                    <div class="hor-scroll">
                                        <input type="hidden" name="count_scripts" value="1" id="count_scripts">
                                        <input type="button" class="button button-primary button-large " style="width: 100px" id="adder" value="Add acr"/>
                                        <table class="form-list1 container">
                                            <tr class="count_scripts wrapper-trr">
                                                <td class="value">
                                                    <input style="width: 200px !important;" type="text" placeholder="Display name (example Google+)" name="name_in_site_1"/>
                                                </td>
                                                <td class="value">
                                                    <input style="width: 270px !important;" type="text" placeholder="ACR Value (script name in the Gluu Server)" name="name_in_gluu_1"/>
                                                </td>
                                                <td class="value">
                                                    <input type="file" accept="image/png" name="images_1"/>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div>
                            <input style="width: 100px" type="submit" class="set_oxd_config button button-primary button-large"';
        if (!$oxd_id) {
            $html.= 'disabled';
        }
        $html.= 'value="Save" name="set_oxd_config"/>
                            <br/><br/>
                        </div>
                    </form>
                </div>
            </div>';
        $html.= '<div class="page" id="sociallogin">';
        if (!$oxd_id){
            $html.= '<div class="mess_red">Please enter OXD configuration to continue.</div><br/>';
        }
        $html.= '<form id="form-apps" name="form-apps" method="post"
                action="?_task=settings&_action=plugin.gluu_sso-save" enctype="multipart/form-data">
                <input type="hidden" name="form_key" value="sugar_crm_config_page"/>
                <div class="mo2f_table_layout"><input type="submit" name="submit" value="Save" style="width:100px;margin-right:2%" class="button button-primary button-large"';
        if (!$oxd_id) {
            $html.= ' disabled ';
        }
        $html.= ' />
                </div>
                <div id="twofactor_list" class="mo2f_table_layout">
        <h3>Gluu login config </h3>
        <hr>
        <p style="font-size:14px">Customize your login icons using a range of shapes and sizes. You can choose different places to display these icons and also customize redirect url after login.</p>
        <br/>
        <hr>
        <br>
        <h3>Customize Login Icons</h3>
        <p>Customize shape, theme and size of the login icons</p>
        <table style="width:100%;display: table;">
            <tbody>
            <tr>
                <td>
                    <b>Shape</b>
                    <b style="margin-left:130px; display: none">Theme</b>
                    <b style="margin-left:130px;">Space between Icons</b>
                    <b style="margin-left:86px;">Size of Icons</b>
                </td>
            </tr>
            <tr>
                <td class="gluuoxd_openid_table_td_checkbox">
                    <input type="radio"';
        if (!$oxd_id) {
            $html.= ' disabled ';
        }
        $html.= 'name="gluuoxd_openid_login_theme" value="circle"
                           onclick="checkLoginButton();gluuOxLoginPreview(document.getElementById(\'gluuox_login_icon_size\').value ,\'circle\',setLoginCustomTheme(),document.getElementById(\'gluuox_login_icon_custom_color\').value,document.getElementById(\'gluuox_login_icon_space\').value)"
                           style="width: auto;" checked>Round
                            <span style="margin-left:106px; display: none">
                                <input type="radio" ';
        if (!$oxd_id) {
            $html.= ' disabled ';
        }
        $html.= 'id="gluuoxd_openid_login_default_radio" name="gluuoxd_openid_login_custom_theme"
                                       value="default"
                                       onclick="checkLoginButton();gluuOxLoginPreview(setSizeOfIcons(), setLoginTheme(),\'default\',document.getElementById(\'gluuox_login_icon_custom_color\').value,document.getElementById(\'gluuox_login_icon_space\').value,document.getElementById(\'gluuox_login_icon_height\').value)"
                                       checked>Default
                            </span>
                            <span style="margin-left:111px;">
                                    <input
                                        style="width:50px"';
        if (!$oxd_id) {
            $html.= ' disabled ';
        }
        $html.= 'onkeyup="gluuOxLoginSpaceValidate(this)" id="gluuox_login_icon_space" name="gluuox_login_icon_space" type="text" value="'.$iconSpace.'" />
                 <input id="gluuox_login_space_plus"';
        if (!$oxd_id) {
            $html.= ' disabled ';
        }
        $html.= 'type="button" value="+"
                                        onmouseup="document.getElementById(\'gluuox_login_icon_space\').value=parseInt(document.getElementById(\'gluuox_login_icon_space\').value)+1;gluuOxLoginPreview(setSizeOfIcons() ,setLoginTheme(),setLoginCustomTheme(),document.getElementById(\'gluuox_login_icon_custom_color\').value,document.getElementById(\'gluuox_login_icon_space\').value)">
                                    <input  id="gluuox_login_space_minus"';
        if (!$oxd_id) {
            $html.= ' disabled ';
        }
        $html.= ' type="button" value="-" onmouseup="document.getElementById(\'gluuox_login_icon_space\').value=parseInt(document.getElementById(\'gluuox_login_icon_space\').value)-1;gluuOxLoginPreview(setSizeOfIcons()  ,setLoginTheme(),setLoginCustomTheme(),document.getElementById(\'gluuox_login_icon_custom_color\').value,document.getElementById(\'gluuox_login_icon_space\').value)">
                            </span>
                            <span id="commontheme" style="margin-left:95px">
                                <input style="width:50px "';
        if (!$oxd_id) {
            $html.= ' disabled ';
        }
        $html.= 'id="gluuox_login_icon_size" onkeyup="gluuOxLoginSizeValidate(this)" name="gluuox_login_icon_custom_size" type="text" value="';
        if ($iconCustomSize) {
            $html.= $iconCustomSize;
        } else {
            $html.= '35';
        }
        $html.= '"><input id="gluuox_login_size_plus" ';
        if (!$oxd_id) {
            $html.= ' disabled';
        }
        $html.= 'type="button" value="+"
                                       onmouseup="document.getElementById(\'gluuox_login_icon_size\').value=parseInt(document.getElementById(\'gluuox_login_icon_size\').value)+1;gluuOxLoginPreview(document.getElementById(\'gluuox_login_icon_size\').value ,setLoginTheme(),setLoginCustomTheme(),document.getElementById(\'gluuox_login_icon_custom_color\').value,document.getElementById(\'gluuox_login_icon_space\').value)">
                                <input id="gluuox_login_size_minus" ';
        if (!$oxd_id) {
            $html.= ' disabled ';
        }
        $html.= 'type="button" value="-"
                                       onmouseup="document.getElementById(\'gluuox_login_icon_size\').value=parseInt(document.getElementById(\'gluuox_login_icon_size\').value)-1;gluuOxLoginPreview(document.getElementById(\'gluuox_login_icon_size\').value ,setLoginTheme(),setLoginCustomTheme(),document.getElementById(\'gluuox_login_icon_custom_color\').value,document.getElementById(\'gluuox_login_icon_space\').value)">
                            </span>
                            <span style="margin-left: 95px; display: none;" class="longbuttontheme">Width:&nbsp;
                                <input style="width:50px"';
        if (!$oxd_id) {
            $html.= ' disabled ';
        }
        $html.= 'id="gluuox_login_icon_width"
                                       onkeyup="gluuOxLoginWidthValidate(this)" name="gluuox_login_icon_custom_width" type="text"
                                       value="'.$iconCustomWidth.'">
                                <input id="gluuox_login_width_plus" ';
        if (!$oxd_id) {
            $html.= ' disabled ';
        }
        $html.= 'type="button" value="+"
                                       onmouseup="document.getElementById(\'gluuox_login_icon_width\').value=parseInt(document.getElementById(\'gluuox_login_icon_width\').value)+1;gluuOxLoginPreview(document.getElementById(\'gluuox_login_icon_width\').value ,setLoginTheme(),setLoginCustomTheme(),document.getElementById(\'gluuox_login_icon_custom_color\').value,document.getElementById(\'gluuox_login_icon_space\').value,document.getElementById(\'gluuox_login_icon_height\').value)">
                                <input id="gluuox_login_width_minus" ';
        if (!$oxd_id) {
            $html.= ' disabled ';
        }
        $html.= 'type="button" value="-"
                                       onmouseup="document.getElementById(\'gluuox_login_icon_width\').value=parseInt(document.getElementById(\'gluuox_login_icon_width\').value)-1;gluuOxLoginPreview(document.getElementById(\'gluuox_login_icon_width\').value ,setLoginTheme(),setLoginCustomTheme(),document.getElementById(\'gluuox_login_icon_custom_color\').value,document.getElementById(\'gluuox_login_icon_space\').value,document.getElementById(\'gluuox_login_icon_height\').value)">
                            </span>
                </td>
            </tr>
            <tr>
                <td class="gluuoxd_openid_table_td_checkbox">
                    <input type="radio"
                           name="gluuoxd_openid_login_theme" ';
        if (!$oxd_id) {
            $html.= ' disabled ';
        }
        $html.= 'value="oval"
                           onclick="checkLoginButton();gluuOxLoginPreview(document.getElementById(\'gluuox_login_icon_size\').value,\'oval\',setLoginCustomTheme(),document.getElementById(\'gluuox_login_icon_custom_color\').value,document.getElementById(\'gluuox_login_icon_space\').value,document.getElementById(\'gluuox_login_icon_size\').value )"
                           style="width: auto;"';
        if ($loginTheme == 'oval') {
            $html.= ' checked';
        }
        $html.= '>Rounded Edges
                        <span style="margin-left:50px; display: none">
                                <input type="radio"';
        if (!$oxd_id) {
            $html.= ' disabled ';
        }
        $html.= 'id="gluuoxd_openid_login_custom_radio"
                                       name="gluuoxd_openid_login_custom_theme" value="custom"
                                       onclick="checkLoginButton();gluuOxLoginPreview(setSizeOfIcons(), setLoginTheme(),\'custom\',document.getElementById(\'gluuox_login_icon_custom_color\').value,document.getElementById(\'gluuox_login_icon_space\').value,document.getElementById(\'gluuox_login_icon_height\').value)"
                                    ';
        if ($loginCustomTheme == 'custom') {
            $html.= ' checked ';
        }
        $html.= '>Custom Background*
                                </span>
                            <span style="margin-left: 256px; display: none;" class="longbuttontheme">Height:
                            <input style="width:50px" ';
        if (!$oxd_id) {
            $html.= ' disabled ';
        }
        $html.= 'id="gluuox_login_icon_height"
                                   onkeyup="gluuOxLoginHeightValidate(this)" name="gluuox_login_icon_custom_height" type="text"
                                   value="';
        if ($iconCustomHeight){
            $html.= $iconCustomHeight;
        } else
            $html.= '35';
        $html.= '">
                            <input id="gluuox_login_height_plus" ';
        if (!$oxd_id) {
            $html.= ' disabled ';
        }
        $html.= 'type="button" value="+"
                                   onmouseup="document.getElementById(\'gluuox_login_icon_height\').value=parseInt(document.getElementById(\'gluuox_login_icon_height\').value)+1;gluuOxLoginPreview(document.getElementById(\'gluuox_login_icon_width\').value,setLoginTheme(),setLoginCustomTheme(),document.getElementById(\'gluuox_login_icon_custom_color\').value,document.getElementById(\'gluuox_login_icon_space\').value,document.getElementById(\'gluuox_login_icon_height\').value)">
                            <input id="gluuox_login_height_minus" ';
        if (!$oxd_id) {
            $html.= ' disabled ';
        }
        $html.= 'type="button" value="-" onmouseup="document.getElementById(\'gluuox_login_icon_height\').value=parseInt(document.getElementById(\'gluuox_login_icon_height\').value)-1;gluuOxLoginPreview(document.getElementById(\'gluuox_login_icon_width\').value,setLoginTheme(),setLoginCustomTheme(),document.getElementById(\'gluuox_login_icon_custom_color\').value,document.getElementById(\'gluuox_login_icon_space\').value,document.getElementById(\'gluuox_login_icon_height\').value)">
                        </span>
                </td>
            </tr>
            <tr>
                <td class="gluuoxd_openid_table_td_checkbox">
                    <input type="radio" ';
        if (!$oxd_id) {
            $html.= '  disabled ';
        }
        $html.= ' name="gluuoxd_openid_login_theme" value="square"
                           onclick="checkLoginButton();gluuOxLoginPreview(document.getElementById(\'gluuox_login_icon_size\').value ,\'square\',setLoginCustomTheme(),document.getElementById(\'gluuox_login_icon_custom_color\').value,document.getElementById(\'gluuox_login_icon_space\').value,document.getElementById(\'gluuox_login_icon_size\').value )"
                           style="width: auto;" ';
        if ($loginTheme == 'square') {
            $html.= ' checked ';
        }
        $html.= '>Square <span style="margin-left:113px; display: none"><input type="color" ';
        if (!$oxd_id) {
            $html.= ' disabled ';
        }
        $html.= 'name="gluuox_login_icon_custom_color" id="gluuox_login_icon_custom_color"
                                               value="'.$iconCustomColor.'"
                                               onchange="gluuOxLoginPreview(setSizeOfIcons(), setLoginTheme(),\'custom\',document.getElementById(\'gluuox_login_icon_custom_color\').value,document.getElementById(\'gluuox_login_icon_space\').value)">
                                    </span>
                </td>
            </tr>
            <tr style="display: none">
                <td class="gluuoxd_openid_table_td_checkbox">
                    <input
                        type="radio" ';
        if (!$oxd_id) {
            $html.= ' disabled ';
        }
        $html.= 'id="iconwithtext" name="gluuoxd_openid_login_theme" value="longbutton"
                        onclick="checkLoginButton();gluuOxLoginPreview(document.getElementById(\'gluuox_login_icon_width\').value ,\'longbutton\',setLoginCustomTheme(),document.getElementById(\'gluuox_login_icon_custom_color\').value,document.getElementById(\'gluuox_login_icon_space\').value,document.getElementById(\'gluuox_login_icon_height\').value)"
                        style="width: auto;" ';
        if ($loginTheme == 'longbutton') {
            $html.= ' checked ';
        }
        $html.= '>Long
                    Button with Text
                </td>
            </tr>
            </tbody>
        </table>
        <br>
        <h3>Preview : </h3>
        <span hidden id="no_apps_text">No apps selected</span>
        <div>';
        foreach ($custom_scripts as $custom_script) {
            $html .= '<img class="gluuox_login_icon_preview"
                     id="gluuox_login_icon_preview_' . $custom_script['value'] . '"
                     src="' . $custom_script['image'] . '"/>';
        }

        $html.= '</div>


        <br><br>
    </div>
</form>
</div>';
        $html.= '<style>
                #helptrouble h1, #helptrouble h2{
                    font-size: 25px;
                    font-weight: bold;
                    color: black;
                }
            </style>
            <div class="page" id="helptrouble">

                <h1><a id="RoundCube_GLUU_SSO_module_0"></a>RoundCube GLUU SSO module</h1>
                <p><img src="https://raw.githubusercontent.com/GluuFederation/gluu-sso-RoundCube-module/master/plugin.jpg" alt="image"></p>
                <p>RoundCube-GLUU-SSO module gives access for login to your RoundCube site, with the help of GLUU server.</p>
                <p>There are already 2 versions of RoundCube-GLUU-SSO (2.4.2 and 2.4.3) modules, each in its turn is working with oxD and GLUU servers.
                    For example if you are using RoundCube-gluu-sso-2.4.3 module, you need to connect with oxD-server-2.4.3.</p>
                <p>Now I want to explain in details how to use module step by step.</p>
                <p>Module will not be working if your host does not have https://.</p>
                <h2><a id="Step_1_Install_Gluuserver_13"></a>Step 1. Install Gluu-server</h2>
                <p>(version 2.4.2 or 2.4.3)</p>
                <p>If you want to use external gluu server, You can not do this step.</p>
                <p><a target="_blank" href="https://www.gluu.org/docs/deployment/">Gluu-server installation gide</a>.</p>
                <h2><a id="Step_2_Download_oxDserver_21"></a>Step 2. Download oxD-server</h2>
                <p><a target="_blank" href="https://ox.gluu.org/maven/org/xdi/oxd-server/2.4.3.Final/oxd-server-2.4.3.Final-distribution.zip">Download oxD-server-2.4.3.Final</a>.</p>
                <h2><a id="Step_3_Unzip_and_run_oXDserver_31"></a>Step 3. Unzip and run oXD-server</h2>
                <ol>
                    <li>Unzip your oxD-server.</li>
                    <li>Open the command line and navigate to the extracted folder in the conf directory.</li>
                    <li>Open oxd-conf.json file.</li>
                    <li>If your server is using 8099 port, please change “port” number to free port, which is not used.</li>
                    <li>Set parameter “op_host”:“Your gluu-server-url (internal or external)”</li>
                    <li>Open the command line and navigate to the extracted folder in the bin directory.</li>
                    <li>For Linux environment, run sh <a href="http://oxd-start.sh">oxd-start.sh</a>&amp;.</li>
                    <li>For Windows environment, run oxd-start.bat.</li>
                    <li>After the server starts, go to Step 4.</li>
                </ol>
                <h2><a id="Step_6_General_73"></a>Step 4. General</h2>
                <p><img src="https://raw.githubusercontent.com/GluuFederation/gluu-sso-RoundCube-module/master/docu/d6.png" alt="General"></p>
                <ol>
                    <li>Admin Email: please add your or admin email address for registrating site in Gluu server.</li>
                    <li>Port number: choose that port which is using oxd-server (see in oxd-server/conf/oxd-conf.json file).</li>
                    <li>Click <code>Next</code> to continue.</li>
                </ol>
                <p>If You are successfully registered in gluu server, you will see bottom page.</p>
                <p><img src="https://raw.githubusercontent.com/GluuFederation/gluu-sso-RoundCube-module/master/docu/d7.png" alt="oxD_id"></p>
                <p>For making sure go to your gluu server / OpenID Connect / Clients and search for your oxD ID</p>
                <p>If you want to reset configurations click on Reset configurations button.</p>
                <h2><a id="Step_8_OpenID_Connect_Configuration_89"></a>Step 5. OpenID Connect Configuration</h2>
                <p>OpenID Connect Configuration page for RoundCube-gluu-sso 2.4.3.</p>
                <h3><a id="Scopes_93"></a>Scopes.</h3>
                <p>You can look all scopes in your gluu server / OpenID Connect / Scopes and understand the meaning of  every scope.
                    Scopes are need for getting loged in users information from gluu server.
                    Pay attention to that, which scopes you are using that are switched on in your gluu server.</p>
                <p>In RoundCube-gluu-sso 2.4.3  you can only enable, disable and delete scope.
                    <img src="https://raw.githubusercontent.com/GluuFederation/gluu-sso-RoundCube-module/master/docu/d9.png" alt="Scopes1"></p>
                <h3><a id="Custom_scripts_104"></a>Custom scripts.</h3>
                <p><img src="https://raw.githubusercontent.com/GluuFederation/gluu-sso-RoundCube-module/master/docu/d10.png" alt="Customscripts"></p>
                <p>You can look all custom scripts in your gluu server / Configuration / Manage Custom Scripts / and enable login type, which type you want.
                    Custom Script represent itself the type of login, at this moment gluu server supports (U2F, Duo, Google +, Basic) types.</p>
                <h3><a id="Pay_attention_to_that_111"></a>Pay attention to that.</h3>
                <ol>
                    <li>Which custom script you enable in your RoundCube site in order it must be switched on in gluu server too.</li>
                    <li>Which custom script you will be enable in OpenID Connect Configuration page, after saving that will be showed in RoundCube Configuration page too.</li>
                    <li>When you create new custom script, both fields are required.</li>
                </ol>
                <h2><a id="Step_9_RoundCube_Configuration_117"></a>Step 6. RoundCube Configuration</h2>
                <h3><a id="Customize_Login_Icons_119"></a>Customize Login Icons</h3>
                <p>Pay attention to that, if custom scripts are not enabled, nothing will be showed.
                    Customize shape, space between icons and size of the login icons.</p>
                <p><img src="https://raw.githubusercontent.com/GluuFederation/gluu-sso-RoundCube-module/master/docu/d11.png" alt="RoundCubeConfiguration"></p>
                <h2><a id="Step_10_Show_icons_in_frontend_126"></a>Step 7. Show icons in frontend</h2>
                <p><img src="https://raw.githubusercontent.com/GluuFederation/gluu-sso-RoundCube-module/master/docu/d12.png" alt="frontend"></p>

            </div>
        </div>
    </div>
</div>';

        return $html;


    }

}
