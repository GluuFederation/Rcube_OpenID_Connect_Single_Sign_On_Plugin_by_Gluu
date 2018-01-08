jQuery(document).ready(function(){
//    var text = '<li class="listitem" id="settingsgluusso">' +
//        '<a href="./?_task=settings&amp;_action=plugin.gluu_sso" >OpenID Connect</a></li>';
//    jQuery('#settings-sections #settings-tabs .listing').append(text);
    var text = '<span class="tablink gluusso " id="settingsgluusso">' +
        '<a href="./?_task=settings&amp;_action=plugin.gluu_sso">OpenID Connect</a></span>';
    jQuery('#settings-tabs').append(text);

});
jQuery(document ).ready(function() {
        var oxd_id = rcmail.gui_objects.oxd_id;
        var get_auth_url = rcmail.gui_objects.get_auth_url;
        var gluu_send_user_check = rcmail.gui_objects.gluu_send_user_check;
        var gluu_is_port_working = rcmail.gui_objects.gluu_is_port_working;
        if(gluu_is_port_working){
            if (oxd_id && gluu_send_user_check == 0) {
                jQuery('form').before("<br/><label style='font-weight: 700;white-space: nowrap;color: #cecece;text-shadow: 0 1px 1px black;text-align: right;font-size: medium;padding-left: 15px;text-indent: -15px;' for='OpenID'><input type='radio' style='width: 13px;height: 13px; padding: 0; margin:0; vertical-align: bottom;position: relative; top: -1px;overflow: hidden;' name='radio' id='OpenID' value='Yes' /> Login by OpenID Provider </label><br/>" +
                    "<label for='base' style='font-weight: 700;white-space: nowrap;color: #cecece;text-shadow: 0 1px 1px black;text-align: right;font-size: medium;padding-left: 15px;text-indent: -15px;'><input type='radio' style='width: 13px;height: 13px; padding: 0; margin:0; vertical-align: bottom;position: relative; top: -1px;overflow: hidden;' name='radio' id='base' value='No' /> Show login form </label><br/>");
                jQuery('form').before('<br/><a href="'+get_auth_url+'" style="background:green; border-radius: 0px;font-weight: 700;white-space: nowrap;color: #cecece;text-shadow: 0 1px 1px black;font-size: medium" class="btn btn-block" id="gluu_login">Login by OpenID Provider</a><br/>');
                jQuery('form').hide();
                jQuery('input:radio[name="radio"]').change(
                    function () {
                        if (jQuery(this).is(':checked') && jQuery(this).val() == 'Yes') {
                            jQuery('#gluu_login').show();
                            jQuery('form').hide();
                        } else {
                            jQuery('#gluu_login').hide();
                            jQuery('form').show();
                        }
                    });
                jQuery('#OpenID').attr('checked', true);
            }else if(oxd_id &&  gluu_send_user_check == 1){
                window.location = get_auth_url;
            }
        }
});
