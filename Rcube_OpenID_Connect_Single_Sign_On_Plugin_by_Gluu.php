<?php
/*
 +-----------------------------------------------------------------------+
 | OpenID Connect Single Sign-On (SSO) Plugin by Gluu for RoundCube                                         |
 |                                                                       |
 | Copyright (C) 2016 Vlad Karapetyan <vlad.karapetyan.1988@mail.ru>     |
 +-----------------------------------------------------------------------+
 */
class Rcube_OpenID_Connect_Single_Sign_On_Plugin_by_Gluu extends rcube_plugin
{
    public $task = 'login|logout|settings';
    private $app;
    private $obj;
    private $config;
    static $gluuDB;

    function __construct(rcube_plugin_api $api)
    {
        parent::__construct($api);
        $RCMAIL = rcmail::get_instance($GLOBALS['env']);
        self::$gluuDB = $RCMAIL->db;
    }

    /*
     * Select data from db:gluu_table.
    */
    public function gluu_db_query_select($gluu_action){
        return self::$gluuDB->query("SELECT `gluu_value` FROM `gluu_table` WHERE `gluu_action` LIKE '".$gluu_action."'")->fetchAll(PDO::FETCH_COLUMN, 0)[0];
    }

    /*
     * Insert data to db:gluu_table.
    */
    public function gluu_db_query_insert($gluu_action, $gluu_value){
        return self::$gluuDB->query("INSERT INTO gluu_table (gluu_action, gluu_value) VALUES ('".$gluu_action."', '".$gluu_value."')");
    }

    /*
     * Update data to db:gluu_table.
    */
    public function gluu_db_query_update($gluu_action, $gluu_value){
        return  self::$gluuDB->query("UPDATE `gluu_table` SET `gluu_value` = '".$gluu_value."' WHERE `gluu_action` LIKE '".$gluu_action."';");
    }

    /*
     * Initializes the plugin.
    */
    public function init()
    {
        $this->add_texts('localization/', false);
        $this->add_hook('startup', array($this, 'startup'));
        $this->include_script('gluu_sso.js');
        $this->app = rcmail::get_instance();
        $this->app->output->add_label($this->gettext('gluu_sso'));

        $src = $this->app->config->get('skin_path') . '/gluu_sso.css';
        if (file_exists($this->home . '/' . $src)) {
            $this->include_stylesheet($src);
        }
        $this->register_action('plugin.gluu_sso', array($this, 'gluu_sso_init'));
        $this->register_action('plugin.gluu_sso-save', array($this, 'gluu_sso_save'));
        $this->add_hook('template_object_loginform', array($this,'gluu_sso_loginform'));
    }

    /*
     * Plugin initialization function.
    */
    public function gluu_sso_init()
    {
        $this->register_handler('plugin.body', array($this, 'gluu_sso_form'));
        $this->app->output->set_pagetitle($this->gettext('gluu_sso'));
        $this->app->output->send('plugin');
    }

    /*
     * OpenID Connect Single Sign-On (SSO) Plugin by Gluu admin page configuration.
    */
    public function admin_html()
    {
        $base_url  = @( $_SERVER["HTTPS"] != 'on' ) ? 'http://' :  'https://';
        $url = $_SERVER['REQUEST_URI']; //returns the current URL
        $parts = explode('/',$url);
        $base_url.= $_SERVER['SERVER_NAME'];
        for ($i = 0; $i < count($parts) - 1; $i++) {
            $base_url .= $parts[$i] . "/";
        }
        $RCMAIL = rcmail::get_instance($GLOBALS['env']);
        $db = $RCMAIL->db;
        $result = $db->query("CREATE TABLE IF NOT EXISTS `gluu_table` (

              `gluu_action` varchar(255) NOT NULL,
              `gluu_value` longtext NOT NULL,
              UNIQUE(`gluu_action`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
        if(!json_decode($this->gluu_db_query_select('scopes'),true)){
            $this->gluu_db_query_insert('scopes',json_encode(array("openid","uma_protection","uma_authorization","profile","email","imapData")));
        }
        if(!json_decode($this->gluu_db_query_select('custom_scripts'),true)){
            $this->gluu_db_query_insert('custom_scripts',json_encode(array(
                        array('name'=>'Basic','image'=>'plugins/rcube_openid_connect_single_sign_on_plugin_by_gluu/GluuOxd_Openid/images/icons/basic.png','value'=>'basic'),
                        array('name'=>'Duo','image'=>'plugins/rcube_openid_connect_single_sign_on_plugin_by_gluu/GluuOxd_Openid/images/icons/duo.png','value'=>'duo'),
                        array('name'=>'OxPush','image'=>'plugins/rcube_openid_connect_single_sign_on_plugin_by_gluu/GluuOxd_Openid/images/icons/oxpush2.png','value'=>'oxpush2'),
                        array('name'=>'Duo','image'=>'plugins/rcube_openid_connect_single_sign_on_plugin_by_gluu/GluuOxd_Openid/images/icons/duo.png','value'=>'duo')
                    )
                )
            );
        }
        if(!json_decode($this->gluu_db_query_select('oxd_config'),true)){
            $this->gluu_db_query_insert('oxd_config',json_encode(array(
                        "op_host" => '',
                        "oxd_host_ip" => '127.0.0.1',
                        "oxd_host_port" =>8099,
                        "admin_email" => '',
                        "authorization_redirect_uri" => $base_url.'?_action=plugin.gluu_sso-login-from-gluu',
                        "logout_redirect_uri" => $base_url.'?_action=plugin.gluu_sso-login-from-gluu',
                        "scope" => ["openid","uma_protection","uma_authorization","profile","email","imapData"],
                        "grant_types" =>["authorization_code"],
                        "response_types" => ["code"],
                        "application_type" => "web",
                        "redirect_uris" => [ $base_url.'?_action=plugin.gluu_sso-login-from-gluu' ],
                        "acr_values" => [],
                    )
                )
            );
        }
        if(!$this->gluu_db_query_select('iconSpace')){
            $this->gluu_db_query_insert('iconSpace','10');
        }
        if(!$this->gluu_db_query_select('iconCustomSize')){
            $this->gluu_db_query_insert('iconCustomSize','50');
        }
        if(!$this->gluu_db_query_select('iconCustomWidth')){
            $this->gluu_db_query_insert('iconCustomWidth','200');
        }
        if(!$this->gluu_db_query_select('iconCustomHeight')){
            $this->gluu_db_query_insert('iconCustomHeight','35');
        }
        if(!$this->gluu_db_query_select('loginCustomTheme')){
            $this->gluu_db_query_insert('loginCustomTheme','default');
        }
        if(!$this->gluu_db_query_select('loginTheme')){
            $this->gluu_db_query_insert('loginTheme','circle');
        }
        if(!$this->gluu_db_query_select('iconCustomColor')){
            $this->gluu_db_query_insert('iconCustomColor','#0000FF');
        }
        $get_scopes =   json_decode($this->gluu_db_query_select('scopes'),true);
        $oxd_config =   json_decode($this->gluu_db_query_select('oxd_config'),true);
        $custom_scripts =   json_decode($this->gluu_db_query_select('custom_scripts'),true);
        $iconSpace = $this->gluu_db_query_select('iconSpace');
        $iconCustomSize = $this->gluu_db_query_select('iconCustomSize');
        $iconCustomWidth = $this->gluu_db_query_select('iconCustomWidth');
        $iconCustomHeight = $this->gluu_db_query_select('iconCustomHeight');
        $loginCustomTheme = $this->gluu_db_query_select('loginCustomTheme');
        $loginTheme = $this->gluu_db_query_select('loginTheme');
        $iconCustomColor = $this->gluu_db_query_select('iconCustomColor');
        $oxd_id = '';
        if($this->gluu_db_query_select('oxd_id')){
            $oxd_id = $this->gluu_db_query_select('oxd_id');
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
            <li id="account_setup"><a data-method="#accountsetup">'.$this->gettext('General').'</a></li>
            <li id="social-sharing-setup"><a data-method="#socialsharing">'.$this->gettext('OpenIDConnect').'</a></li>
            <li id="social-login-setup"><a data-method="#sociallogin">'.$this->gettext('rConfig').'</a></li>
            <li id="help_trouble"><a data-method="#helptrouble">'.$this->gettext('helpTrouble').'</a></li>
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
                                    '.$this->gettext('messageConnectProvider').'
                                </div>
                                <br/>
                                <div><h3>'.$this->gettext('registerMessageConnectProvider').'</h3></div>
                                <hr>
                                <div class="mess_red">'.$this->gettext('linkToGluu').'</div>
                                <div class="mess_red">'.$this->gettext('Instructions').'</div>
                                <hr>
                                <div>
                                    <table class="table">
                                        <tr>
                                            <td><b><font color="#FF0000">*</font>'.$this->gettext('adminEmail').'</b></td>
                                            <td><input class="" type="email" name="loginemail" id="loginemail"
                                                       autofocus="true" required placeholder="person@example.com"
                                                       style="width:400px;"
                                                       value="'.$oxd_config['admin_email'].'"/>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><b><font color="#FF0000">*</font>'.$this->gettext('gluuServerUrl').'</b></td>
                                            <td><input class="" type="url" name="gluuServerUrl" id="gluuServerUrl"
                                                       autofocus="true" required placeholder="Insert gluu server url"
                                                       style="width:400px;"
                                                       value="'.$oxd_config['op_host'].'"/>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><b><font color="#FF0000">*</font>'.$this->gettext('portNumber').'</b></td>
                                            <td>
                                                <input class="" type="number" name="oxd_port" min="0" max="65535"
                                                       value="'.$oxd_config['oxd_host_port'].'"
                                                       style="width:400px;" placeholder="'.$this->gettext('EnterportNumber').'"/>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                                <br/>
                                <div><input type="submit" name="submit" value="'.$this->gettext('next').'" style="width: 120px" class=""/></div>
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
                                <h3 style="color: #45a8ff" class="sc"><img style=" height: 45px; margin-left: 20px;" src="plugins/rcube_openid_connect_single_sign_on_plugin_by_gluu/GluuOxd_Openid/images/icons/ox.png"/>&nbsp; server config</h3>
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
                        <p><input style="width: 200px; background-color: red !important; cursor: pointer" type="submit" class="button button-primary " value="'.$this->gettext('resetConfig').'" name="resetButton"/></p>
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
                                    <h3 style="color: #00aa00" class="sc"><img style="height: 45px; margin-left: 30px;" src="plugins/rcube_openid_connect_single_sign_on_plugin_by_gluu/GluuOxd_Openid/images/icons/gl.png"/> &nbsp; server config
                                    </h3>
                                </div>
                            </div>
                            <div class="entry-edit" >
                                <div class="entry-edit-head" style="background-color: #00aa00 !important;">
                                    <h4 class="icon-head head-edit-form fieldset-legend">'.$this->gettext('allScopes').'</h4>
                                </div>
                                <div class="fieldset">
                                    <div class="hor-scroll">
                                        <table class="form-list">
                                            <tr class="wrapper-trr">';
        foreach ($get_scopes as $scop){
            $html.='<td class="value">';
            if ($scop == 'openid' || $scop == 'uma_protection' || $scop == 'uma_authorization'){
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
            if ($scop == 'openid' || $scop == 'uma_protection' || $scop == 'uma_authorization') $html.= ' disabled   />';
            $html.= '<label for="'.$scop.'">'.$scop.'</label>
                                                    </td>';
        }
        $html.= '</tr>
        </table>
        <table class="form-list" style="text-align: center">
                <tr class="wrapper-tr" style="text-align: center">
                    <th style="border: 1px solid #43ffdf; width: 70px;text-align: center"><h3>N</h3></th>
                    <th style="border: 1px solid #43ffdf;width: 200px;text-align: center"><h3>'.$this->gettext('name').'</h3></th>
                    <th style="border: 1px solid #43ffdf;width: 200px;text-align: center"><h3>'.$this->gettext('delete').'</h3></th>
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
            if ($scop == 'openid'){

            }
            elseif ($scop == 'uma_protection'){

            }
            elseif ($scop == 'uma_authorization'){

            }else{
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
                                    <h4 class="icon-head head-edit-form fieldset-legend">'.$this->gettext('addScopes').'</h4>
                                </div>
                                <div class="fieldset">
                                    <input type="button" id="adding" class="button button-primary button-large add" style="width: 100px" value="'.$this->gettext('addScopes').'"/>
                                    <div class="hor-scroll">
                                        <table class="form-list5 container">
                                            <tr class="wrapper-tr">
                                                <td class="value">
                                                    <input type="text" placeholder="'.$this->gettext('InputScopeName').'" name="scope_name[]"/>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="entry-edit" >
                                <div class="entry-edit-head" style="background-color: #00aa00 !important;">
                                    <h4 class="icon-head head-edit-form fieldset-legend">'.$this->gettext('allCustomScripts').'</h4>
                                </div>
                                <div class="fieldset">
                                    <div class="hor-scroll">
                                        '.$this->gettext('manageAuthentication').'
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

            if ($this->gluu_db_query_select($custom_script['value']."Enable")) $html.= 'checked';
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
                                                <th style="border: 1px solid #43ffdf;width: 200px;text-align: center"><h3>'.$this->gettext('DisplayName').'</h3></th>
                                                <th style="border: 1px solid #43ffdf;width: 200px;text-align: center"><h3>'.$this->gettext('ACRvalue').'</h3></th>
                                                <th style="border: 1px solid #43ffdf;width: 200px;text-align: center"><h3>'.$this->gettext('Image').'</h3></th>
                                                <th style="border: 1px solid #43ffdf;width: 200px;text-align: center"><h3>'.$this->gettext('delete').'</h3></th>
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
                                    <h4 class="icon-head head-edit-form fieldset-legend">'.$this->gettext('multipleCustomScripts').'</h4>
                                    <p style="color:#cc0b07; font-style: italic; font-weight: bold;font-size: larger"> '.$this->gettext('BothFields').'</p>
                                </div>
                                <div class="fieldset">
                                    <div class="hor-scroll">
                                        <input type="hidden" name="count_scripts" value="1" id="count_scripts">
                                        <input type="button" class="button button-primary button-large " style="width: 100px" id="adder" value="'.$this->gettext('Addacr').'"/>
                                        <table class="form-list1 container">
                                            <tr class="count_scripts wrapper-trr">
                                                <td class="value">
                                                    <input style="width: 200px !important;" type="text" placeholder="'.$this->gettext('exampleGoogle').'" name="name_in_site_1"/>
                                                </td>
                                                <td class="value">
                                                    <input style="width: 270px !important;" type="text" placeholder="'.$this->gettext('scriptName').'" name="name_in_gluu_1"/>
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
                            <input style="width: 100px" type="submit" class="button button-primary button-large"';
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
            $html.= '<div class="mess_red">'.$this->gettext('OXDConfiguration').'</div><br/>';
        }
        $html.= '<form id="form-apps" name="form-apps" method="post"
                action="?_task=settings&_action=plugin.gluu_sso-save" enctype="multipart/form-data">
                <input type="hidden" name="form_key" value="roundcube_crm_config_page"/>
                <div class="mo2f_table_layout"><input type="submit" name="submit" value="'.$this->gettext('Save').'" style="width:100px;margin-right:2%" class="button button-primary button-large"';
        if (!$oxd_id) {
            $html.= ' disabled ';
        }
        $html.= ' />
                </div>
                <div id="twofactor_list" class="mo2f_table_layout">
        <h3>'.$this->gettext('GluuLoginConfig').'</h3>
        <hr>
        <p style="font-size:14px">'.$this->gettext('CustomizeYourLogin').'
        </p>
        <br/>
        <hr>
        <br>
        <h3>'.$this->gettext('CustomizeLoginIcons').'</h3>
        <p>'.$this->gettext('CustomizeShape').'</p>
        <table style="width:100%;display: table;">
            <tbody>
            <tr>
                <td>
                    <b>Shape</b>
                    <b style="margin-left:130px; display: none">'.$this->gettext('Theme').'</b>
                    <b style="margin-left:130px;">'.$this->gettext('SpaceBetweenIcons').'</b>
                    <b style="margin-left:86px;">'.$this->gettext('SizeofIcons').'</b>
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
                           style="width: auto;" checked>'.$this->gettext('Round').'
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
                            <span id="commontheme" style="margin-left:135px">
                                <input style="width:50px; margin-right: 5px "';
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
                            <span style="margin-left: 95px; display: none;" class="longbuttontheme">'.$this->gettext('Width').'
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
        $html.= '>'.$this->gettext('RoundedEdges').'
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
        $html.= '>'.$this->gettext('CustomBackground').'
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
        $html.= '>'.$this->gettext('LongButton').'
                </td>
            </tr>
            </tbody>
        </table>
        <br>
        <h3>Preview : </h3>
        <span hidden id="no_apps_text">'.$this->gettext('NoApps').'</span>
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
                '.$this->gettext('doocumentation243').'
            </div>
        </div>
    </div>
</div>';

        return $html;


    }

    /*
     * Plugin page showing function.
    */
    public function gluu_sso_form()
    {
        // add taskbar button

        $boxTitle = html::div(array('id' => "prefs-title", 'class' => 'boxtitle'), $this->gettext('hederGluu'));
        $this->include_stylesheet('GluuOxd_Openid/css/gluu-oxd-css.css');
        $this->include_script('GluuOxd_Openid/js/scope-custom-script.js');

        $tableHtml=$this->admin_html();
        unset($_SESSION['message_error']);
        unset($_SESSION['message_success']);
        return html::div(array('class' => ''),$boxTitle . html::div(array('class' => "boxcontent"), $tableHtml ));
    }

    /*
     * Save data and configurations function.
     */
    public function gluu_sso_save()
    {
        require_once("GluuOxd_Openid/oxd-rp/Register_site.php");
        require_once("GluuOxd_Openid/oxd-rp/Update_site_registration.php");
        $base_url  = @( $_SERVER["HTTPS"] != 'on' ) ? 'http://' :  'https://';
        $url = $_SERVER['REQUEST_URI']; //returns the current URL
        $parts = explode('/',$url);
        $base_url.= $_SERVER['SERVER_NAME'];
        for ($i = 0; $i < count($parts) - 1; $i++) {
            $base_url .= $parts[$i] . "/";
        }
        $RCMAIL = rcmail::get_instance($GLOBALS['env']);
        $db = $RCMAIL->db;

        if( isset( $_REQUEST['form_key'] ) and strpos( $_REQUEST['form_key'], 'general_register_page' )               !== false ) {

            $config_option = json_encode(array(
                "op_host" => $_POST['gluuServerUrl'],
                "oxd_host_ip" => '127.0.0.1',
                "oxd_host_port" =>$_POST['oxd_port'],
                "admin_email" => $_POST['loginemail'],
                "authorization_redirect_uri" => $base_url.'?_action=plugin.gluu_sso-login-from-gluu',
                "logout_redirect_uri" => $base_url.'?_task=logout',
                "scope" => ["openid","profile","email","imapData"],
                "grant_types" =>["authorization_code"],
                "response_types" => ["code"],
                "application_type" => "web",
                "redirect_uris" => [ $base_url.'?_action=plugin.gluu_sso-login-from-gluu'],
                "acr_values" => [],
            ));
            $this->gluu_db_query_update('oxd_config', $config_option);
            $config_option = array(
                "op_host" => $_POST['gluuServerUrl'],
                "oxd_host_ip" => '127.0.0.1',
                "oxd_host_port" =>$_POST['oxd_port'],
                "admin_email" => $_POST['loginemail'],
                "authorization_redirect_uri" => $base_url.'?_action=plugin.gluu_sso-login-from-gluu',
                "logout_redirect_uri" => $base_url.'?_task=logout',
                "scope" => ["openid","profile","email","imapData"],
                "grant_types" =>["authorization_code"],
                "response_types" => ["code"],
                "application_type" => "web",
                "redirect_uris" => [ $base_url.'?_action=plugin.gluu_sso-login-from-gluu' ],
                "acr_values" => [],
            );
            $register_site = new Register_site();
            $register_site->setRequestOpHost($config_option['op_host']);
            $register_site->setRequestAcrValues($config_option['acr_values']);
            $register_site->setRequestAuthorizationRedirectUri($config_option['authorization_redirect_uri']);
            $register_site->setRequestRedirectUris($config_option['redirect_uris']);
            $register_site->setRequestGrantTypes($config_option['grant_types']);
            $register_site->setRequestResponseTypes(['code']);
            $register_site->setRequestLogoutRedirectUri($config_option['logout_redirect_uri']);
            $register_site->setRequestContacts([$config_option["admin_email"]]);
            $register_site->setRequestApplicationType('web');
            $register_site->setRequestClientLogoutUri($config_option['logout_redirect_uri']);
            $register_site->setRequestScope($config_option['scope']);
            $status = $register_site->request();
            if(!$status['status']){
                $_SESSION['message_error'] = $status['message'];
                $RCMAIL->output->redirect('plugin.gluu_sso');
            }
            if($register_site->getResponseOxdId()){
                $oxd_id = $register_site->getResponseOxdId();
                if(!$this->gluu_db_query_select('oxd_id')){
                    $this->gluu_db_query_insert('oxd_id',$oxd_id);
                }
            }
            $_SESSION['message_success'] = $this->gettext('messageSiteRegisteredSuccessful');
            $RCMAIL->output->redirect('plugin.gluu_sso');
        }
        else if( isset( $_REQUEST['form_key'] ) and strpos( $_REQUEST['form_key'], 'openid_config_delete_scop' )           !== false ) {

            $get_scopes =   json_decode($this->gluu_db_query_select('scopes'),true);
            $up_cust_sc =  array();

            foreach($get_scopes as $custom_scop){
                if($custom_scop !=$_REQUEST['value_scope']){
                    array_push($up_cust_sc,$custom_scop);
                }
            }

            $get_scopes = json_encode($up_cust_sc);
            $this->gluu_db_query_update('scopes', $get_scopes);
            $_SESSION['message_success'] = $this->gettext('messageScopeDeletedSuccessful');
            $RCMAIL->output->redirect('plugin.gluu_sso');
        }
        else if( isset( $_REQUEST['form_key'] ) and strpos( $_REQUEST['form_key'], 'general_oxd_id_reset' )                !== false and !empty($_REQUEST['resetButton'])) {
            $db->query("DROP TABLE IF EXISTS `gluu_table`;");
            $_SESSION['message_success'] = $this->gettext('messageConfigurationsDeletedSuccessful');
            $RCMAIL->output->redirect('plugin.gluu_sso');
        }
        else if( isset( $_REQUEST['form_key'] ) and strpos( $_REQUEST['form_key'], 'openid_config_delete_custom_scripts' ) !== false ) {
            $get_scopes =   json_decode($this->gluu_db_query_select('custom_scripts'),true);
            $up_cust_sc =  array();
            foreach($get_scopes as $custom_scop){
                if($custom_scop['value'] !=$_REQUEST['value_script']){
                    array_push($up_cust_sc,$custom_scop);
                }
            }
            $get_scopes = json_encode($up_cust_sc);
            $this->gluu_db_query_update('custom_scripts', $get_scopes);
            $_SESSION['message_success'] = $this->gettext('messageScriptDeletedSuccessful');
            $RCMAIL->output->redirect('plugin.gluu_sso');
        }
        else if( isset( $_REQUEST['form_key'] ) and strpos( $_REQUEST['form_key'], 'roundcube_crm_config_page' )               !== false ) {
            $this->gluu_db_query_update('loginTheme', $_REQUEST['gluuoxd_openid_login_theme']);
            $this->gluu_db_query_update('loginCustomTheme', $_REQUEST['gluuoxd_openid_login_custom_theme']);
            $this->gluu_db_query_update('iconSpace', $_REQUEST['gluuox_login_icon_space']);
            $this->gluu_db_query_update('iconCustomSize', $_REQUEST['gluuox_login_icon_custom_size']);
            $this->gluu_db_query_update('iconCustomWidth', $_REQUEST['gluuox_login_icon_custom_width']);
            $this->gluu_db_query_update('iconCustomHeight', $_REQUEST['gluuox_login_icon_custom_height']);
            $this->gluu_db_query_update('iconCustomColor', $_REQUEST['gluuox_login_icon_custom_color']);
            $_SESSION['message_success'] = $this->gettext('messageYourConfiguration');
            $RCMAIL->output->redirect('plugin.gluu_sso');
        }
        else if( isset( $_REQUEST['form_key'] ) and strpos( $_REQUEST['form_key'], 'openid_config_page' )                  !== false ) {
            $params = $_REQUEST;
            $message_success = '';
            $message_error = '';
            if(!empty($params['scope']) && isset($params['scope'])){
                $oxd_config =   json_decode($this->gluu_db_query_select('oxd_config'),true);
                $oxd_config['scope'] = $params['scope'];
                $oxd_config = json_encode($oxd_config);
                $this->gluu_db_query_update('oxd_config',$oxd_config);
            }
            if(!empty($params['scope_name']) && isset($params['scope_name'])){
                $get_scopes =   json_decode($this->gluu_db_query_select('scopes'),true);
                foreach($params['scope_name'] as $scope){
                    if($scope && !in_array($scope,$get_scopes)){
                        array_push($get_scopes, $scope);
                    }
                }
                $get_scopes = json_encode($get_scopes);
                $this->gluu_db_query_update('scopes',$get_scopes);
            }

            $custom_scripts =   json_decode($this->gluu_db_query_select('custom_scripts'),true);

            foreach($custom_scripts as $custom_script){
                $action = $custom_script['value']."Enable";
                $value = $params['gluuoxd_openid_'.$custom_script['value'].'_enable'];
                $typeLogin =  $this->gluu_db_query_select($custom_script['value']."Enable");
                if(!$typeLogin){
                    $this->gluu_db_query_insert($action,$value);
                }
                if($value != NULL){
                    $this->gluu_db_query_update($action,'1');
                }else{
                    $this->gluu_db_query_update($action,'0');
                }

            }

            if(isset($params['count_scripts'])){
                $error_array = array();
                $error = true;

                $custom_scripts = json_decode($this->gluu_db_query_select('custom_scripts'),true);
                for($i=1; $i<=$params['count_scripts']; $i++){
                    if(isset($params['name_in_site_'.$i]) && !empty($params['name_in_site_'.$i]) && isset($params['name_in_gluu_'.$i]) && !empty($params['name_in_gluu_'.$i]) && isset($_FILES['images_'.$i]) && !empty($_FILES['images_'.$i])){
                        foreach($custom_scripts as $custom_script){
                            if($custom_script['value'] == $params['name_in_gluu_'.$i] || $custom_script['name'] == $params['name_in_site_'.$i]){
                                $error = false;
                                array_push($error_array, $i);
                            }
                        }
                        if($error){
                            $target_dir = "plugins/rcube_openid_connect_single_sign_on_plugin_by_gluu/GluuOxd_Openid/images/icons/";
                            $target_file = $target_dir . basename($_FILES['images_'.$i]["name"]);
                            $uploadOk = 1;
                            $imageFileType = pathinfo($target_file,PATHINFO_EXTENSION);
                            if (file_exists($target_file)) {
                                $target_file= $target_dir.$this->file_newname($target_dir, basename($_FILES['images_'.$i]["name"]));

                            }

                            if (move_uploaded_file($_FILES['images_'.$i]["tmp_name"], $target_file)) {
                                array_push($custom_scripts, array('name'=>$params['name_in_site_'.$i],'image'=>$target_file,'value'=>$params['name_in_gluu_'.$i]));
                                $custom_scripts_json = json_encode($custom_scripts);
                                $this->gluu_db_query_update('custom_scripts', $custom_scripts_json);

                            } else {
                                $message_error.= $this->gettext('messageSorryUploading').$_FILES['images_'.$i]["name"].' '.$this->gettext('file').".<br/>";
                                break;
                            }

                        }else{
                            $message_error.=$this->gettext('name').' = '.$params['name_in_site_'.$i].' '.$this->gettext('or'). '  value = '. $params['name_in_gluu_'.$i] .' '.$this->gettext('isExist').'<br/>';
                            break;
                        }
                    }else{
                        if(!empty($params['name_in_site_'.$i]) || !empty($params['name_in_gluu_'.$i]) || !empty($_FILES['images_'.$i]["name"])){
                            $message_error.=$this->gettext('necessaryToFill').'<br/>';
                        }
                    }
                }

            }
            $config_option =   json_decode($this->gluu_db_query_select('oxd_config'),true);
            $update_site_registration = new Update_site_registration();
            $update_site_registration->setRequestOxdId($this->gluu_db_query_select("oxd_id"));
            $update_site_registration->setRequestAcrValues($config_option['acr_values']);
            $update_site_registration->setRequestAuthorizationRedirectUri($config_option['authorization_redirect_uri']);
            $update_site_registration->setRequestRedirectUris($config_option['redirect_uris']);
            $update_site_registration->setRequestGrantTypes($config_option['grant_types']);
            $update_site_registration->setRequestResponseTypes(['code']);
            $update_site_registration->setRequestLogoutRedirectUri($config_option['logout_redirect_uri']);
            $update_site_registration->setRequestContacts([$config_option['admin_email']]);
            $update_site_registration->setRequestApplicationType('web');
            $update_site_registration->setRequestClientLogoutUri($config_option['logout_redirect_uri']);
            $update_site_registration->setRequestScope($config_option['scope']);
            $status = $update_site_registration->request();
            if(!$status['status']){
                $_SESSION['message_error'] = $status['message'];
                $RCMAIL->output->redirect('plugin.gluu_sso');
            }
            if($update_site_registration->getResponseOxdId()){
                $oxd_id = $update_site_registration->getResponseOxdId();
                $this->gluu_db_query_update('oxd_id', $oxd_id);

            }
            $_SESSION['message_success'] = $this->gettext('messageOpenIDConnectConfiguration');
            $_SESSION['message_error'] = $message_error;
            $RCMAIL->output->redirect('plugin.gluu_sso');
            exit;
        }
        $RCMAIL->output->redirect('plugin.gluu_sso');
    }

    /*
     * Changing uploaded file name
    */
    function file_newname($path, $filename){
        if ($pos = strrpos($filename, '.')) {
            $name = substr($filename, 0, $pos);
            $ext = substr($filename, $pos);
        } else {
            $name = $filename;
        }

        $newpath = $path.'/'.$filename;
        $newname = $filename;
        $counter = 0;
        while (file_exists($newpath)) {
            $newname = $name .'_'. $counter . $ext;
            $newpath = $path.'/'.$newname;
            $counter++;
        }

        return $newname;
    }

    /*
     * Checking request and response from login page.
    */
    public function startup($args)
    {

        if( isset($_SESSION['user_oxd_access_token']) && !empty($_SESSION['user_oxd_access_token'])  && isset( $_REQUEST['_task'] ) and strpos( $_REQUEST['_task'], 'logout' ) !== false ){
            session_start();
            require_once("GluuOxd_Openid/oxd-rp/Logout.php");
            $RCMAIL = rcmail::get_instance($GLOBALS['env']);
            $db = $RCMAIL->db;
            $oxd_id =  $this->gluu_db_query_select("oxd_id");

            $conf = json_decode($this->gluu_db_query_select("oxd_config"),true);;
            $logout = new Logout();
            $logout->setRequestOxdId($oxd_id);
            $logout->setRequestIdToken($_SESSION['user_oxd_id_token']);
            $logout->setRequestPostLogoutRedirectUri($conf['logout_redirect_uri']);
            $logout->setRequestSessionState($_SESSION['session_state']);
            $logout->setRequestState($_SESSION['state']);
            $logout->request();
            session_destroy();
            unset($_SESSION['user_oxd_access_token']);
            unset($_SESSION['user_oxd_id_token']);
            unset($_SESSION['session_state']);
            unset($_SESSION['state']);
            header("Location: ".$logout->getResponseObject()->data->uri);
            exit;

        }
        if(isset($_REQUEST['app_name']) && isset( $_REQUEST['_action'] ) and strpos( $_REQUEST['_action'], 'plugin.gluu_sso-login' )               !== false ){
            require_once("GluuOxd_Openid/oxd-rp/Get_authorization_url.php");
            $RCMAIL = rcmail::get_instance($GLOBALS['env']);
            $db = $RCMAIL->db;
            $oxd_id =  $this->gluu_db_query_select("oxd_id");
            $get_authorization_url = new Get_authorization_url();
            $get_authorization_url->setRequestOxdId($oxd_id);
            $get_authorization_url->setRequestAcrValues([$_REQUEST['app_name']]);
            $get_authorization_url->request();

            if($get_authorization_url->getResponseAuthorizationUrl()){
                header( "Location: ". $get_authorization_url->getResponseAuthorizationUrl() );
                exit;
            }else{
                echo '<p style="color: red">'.$this->gettext('messageSwitchedOn').'</p>';
            }

        }
        if(isset( $_REQUEST['_action'] ) and strpos( $_REQUEST['_action'], 'plugin.gluu_sso-login-from-gluu' )               !== false ){

            require_once("GluuOxd_Openid/oxd-rp/Get_tokens_by_code.php");
            require_once("GluuOxd_Openid/oxd-rp/Get_user_info.php");
            $RCMAIL = rcmail::get_instance($GLOBALS['env']);
            $db = $RCMAIL->db;
            $RCMAIL->kill_session();
            $oxd_id =  $this->gluu_db_query_select("oxd_id");

            $http = isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? "https://" : "http://";
            $parts = parse_url($http . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
            parse_str($parts['query'], $query);
            $config_option = json_decode($this->gluu_db_query_select("oxd_config"),true);;
            $get_tokens_by_code = new Get_tokens_by_code();
            $get_tokens_by_code->setRequestOxdId($oxd_id);
            $get_tokens_by_code->setRequestCode($_REQUEST['code']);
            $get_tokens_by_code->setRequestState($_REQUEST['state']);
            $get_tokens_by_code->setRequestScopes($config_option["scope"]);
            $get_tokens_by_code->request();
            $get_tokens_by_code_array = $get_tokens_by_code->getResponseObject()->data->id_token_claims;

            $get_user_info = new Get_user_info();
            $get_user_info->setRequestOxdId($oxd_id);
            $get_user_info->setRequestAccessToken($get_tokens_by_code->getResponseAccessToken());
            $get_user_info->request();
            $get_user_info_array = $get_user_info->getResponseObject()->data->claims;
            $_SESSION['user_oxd_id_token']  = $get_tokens_by_code->getResponseIdToken();
            $_SESSION['user_oxd_access_token']  = $get_tokens_by_code->getResponseAccessToken();
            $_SESSION['session_state'] = $_REQUEST['session_state'];
            $_SESSION['state'] = $_REQUEST['state'];
            $address = $get_user_info_array->address[0];
            $address_object = json_decode($address);
            $reg_email = '';
            if($get_user_info_array->email[0]){
                $reg_email = $get_user_info_array->email[0];
            }elseif($get_tokens_by_code_array->email[0]){
                $reg_email = $get_tokens_by_code_array->email[0];
            }

            $auth = $RCMAIL->plugins->exec_hook('authenticate', array(
                'host' => $get_user_info_array->imapHost[0],
                'user' => trim(rcube_utils::get_input_value('_user', $get_user_info_array->imapUsername[0])),
                'pass' => rcube_utils::get_input_value('_pass', $get_user_info_array->imapPassword[0], true,
                    $RCMAIL->config->get('password_charset', 'ISO-8859-1')),
                'cookiecheck' => true,
                'valid'       => true,
            ));

            if($RCMAIL->login($get_user_info_array->imapUsername[0], $get_user_info_array->imapPassword[0],$get_user_info_array->imapHost[0], $auth['cookiecheck'])){
                $RCMAIL->session->remove('temp');
                $RCMAIL->session->regenerate_id(false);

                // send auth cookie if necessary
                $RCMAIL->session->set_auth_cookie();

                // log successful login
                $RCMAIL->log_login();

                // restore original request parameters
                $query = array();

                // allow plugins to control the redirect url after login success
                $redir = $RCMAIL->plugins->exec_hook('login_after', $query + array('_task' => 'mail'));
                unset($redir['abort'], $redir['_err']);
                $query = array('_action' => 'plugin.gluu_sso');
                $OUTPUT = new rcmail_html_page();
                $redir = $RCMAIL->plugins->exec_hook('login_after', $query + array('_task' => 'settings'));
                $RCMAIL->session->set_auth_cookie();
                $OUTPUT->redirect($redir, 0, true);
            }else{
                echo '<p style="color: red">'.$this->gettext('problemImapConnection').'</p>';
            }


        }
    }
    /*
     * Sending config info to gluu_sso.js.
    */
    function gluu_sso_loginform($content)
    {

        $base_url  = @( $_SERVER["HTTPS"] != 'on' ) ? 'http://' :  'https://';
        $url = $_SERVER['REQUEST_URI']; //returns the current URL
        $parts = explode('/',$url);
        $base_url.= $_SERVER['SERVER_NAME'];
        for ($i = 0; $i < count($parts) - 1; $i++) {
            $base_url .= $parts[$i] . "/";
        }
        $oxd_id = $this->gluu_db_query_select('oxd_id');
        $get_scopes =   json_decode($this->gluu_db_query_select('scopes'),true);
        $oxd_config =   json_decode($this->gluu_db_query_select('oxd_config'),true);
        $custom_scripts =   json_decode($this->gluu_db_query_select('custom_scripts'),true);
        $iconSpace = $this->gluu_db_query_select('iconSpace');
        $iconCustomSize = $this->gluu_db_query_select('iconCustomSize');
        $iconCustomWidth = $this->gluu_db_query_select('iconCustomWidth');
        $iconCustomHeight = $this->gluu_db_query_select('iconCustomHeight');
        $loginCustomTheme = $this->gluu_db_query_select('loginCustomTheme');
        $loginTheme = $this->gluu_db_query_select('loginTheme');
        $iconCustomColor = $this->gluu_db_query_select('iconCustomColor');
        foreach($custom_scripts as $custom_script){
            $enableds[] = array('enable' => $this->gluu_db_query_select($custom_script['value']."Enable"),
                'value' => $custom_script['value'],
                'name' => $custom_script['name'],
                'image' => $custom_script['image']
            );
        }
        $enableds = array();
        foreach($custom_scripts as $custom_script){
            $enableds[] = array('enable' => $this->gluu_db_query_select($custom_script['value']."Enable"),
                'value' => $custom_script['value'],
                'name' => $custom_script['name'],
                'image' => $custom_script['image']
            );
        }
        $this->app->output->add_gui_object('oxd_id', $oxd_id);
        $this->app->output->add_gui_object('base_url', $base_url);
        $this->app->output->add_gui_object('custom_scripts_enabled', json_encode($enableds));
        $this->app->output->add_gui_object('get_scopes', json_encode($get_scopes));
        $this->app->output->add_gui_object('oxd_config', json_encode($oxd_config));
        $this->app->output->add_gui_object('custom_scripts', json_encode($custom_scripts));
        $this->app->output->add_gui_object('iconSpace', $iconSpace);
        $this->app->output->add_gui_object('iconCustomSize', $iconCustomSize);
        $this->app->output->add_gui_object('iconCustomWidth', $iconCustomWidth);
        $this->app->output->add_gui_object('iconCustomHeight', $iconCustomHeight);
        $this->app->output->add_gui_object('loginCustomTheme', $loginCustomTheme);
        $this->app->output->add_gui_object('loginTheme', $loginTheme);
        $this->app->output->add_gui_object('iconCustomColor', $iconCustomColor);
        return $content;
    }
}
