$(document).ready(function(){
    var text = '<li class="listitem" id="settingsgluusso">' +
        '<a href="./?_task=settings&amp;_action=plugin.gluu_sso" >OpenID Connect Single Sign-On (SSO) Plugin by Gluu 2.4.4</a></li>';
    $('#settings-sections #settings-tabs .listing').append(text);
    var text = '<span class="tablink gluusso " id="settingstabgluusso">' +
        '<a href="./?_task=settings&amp;_action=plugin.gluu_sso">OpenID Connect Single Sign-On (SSO) Plugin by Gluu 2.4.4</a></span>';
    $('#tabsbar').append(text);

});

var base_url = rcmail.gui_objects.base_url;
function socialLogin(appName){
    window.location.href =base_url+'?_action=plugin.gluu_sso-login&app_name='+appName;
}
if (window.rcmail) {

    var loginTheme = rcmail.gui_objects.loginTheme;
    var oxd_id = rcmail.gui_objects.oxd_id;
    var iconSpace = rcmail.gui_objects.iconSpace;
    var iconCustomSize = rcmail.gui_objects.iconCustomSize;
    var loginCustomTheme = rcmail.gui_objects.loginCustomTheme;
    var array = jQuery.parseJSON(rcmail.gui_objects.custom_scripts_enabled);
    rcmail.addEventListener('init', function() {
        var	text = '';
        if(oxd_id) {
            text += "<div>";
            text += '<style>' +
                '.gluuox_login_icon_preview{' +
                'width:35px;' +
                'cursor:pointer;' +
                'display:inline;' +
                '}' +
                '.customer-account-login .page-title{' +
                'margin-top: -100px !important;' +
                '}' +
                '</style>';
            if (loginTheme != 'longbutton') {
                text += '<style>.gluuOx_custom_login_icon_preview{cursor:pointer;}</style>';
                if (loginTheme == 'circle') {
                    text += '<style> .gluuox_login_icon_preview, .gluuOx_custom_login_icon_preview{border-radius: 999px !important;}</style>';
                } else if (loginTheme == 'oval') {
                    text += '<style> .gluuox_login_icon_preview, .gluuOx_custom_login_icon_preview{border-radius: 5px !important;}</style>';
                }
                if (loginCustomTheme != 'custom') {
                    var cl = '';
                    array.forEach(function (object) {
                        if (object.enable == 1) {
                            cl = "socialLogin('" + object.value + "')";
                            text += '<img class="gluuox_login_icon_preview" id="gluuox_login_icon_preview_' + object.value + '" src="' + object.image + '"' +
                                'style="margin-left: ' + iconSpace + 'px;  height:' + iconCustomSize + 'px; width:' + iconCustomSize + 'px;" onclick="' + cl + '"  />';
                        }
                    });

                }
            }
            $('.box-inner').append(text);
            $('.boxcontent').append(text);
        }
    });
}
