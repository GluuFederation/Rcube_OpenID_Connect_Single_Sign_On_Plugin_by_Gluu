/**
 * Created by Vlad on 3/20/2016.
 */

function test_add() {
    var wrapper1 = '<p class="role_p" style="padding-top: 10px">' +
        '<input class="form-control"  type="text" name="gluu_new_role[]" placeholder="Input role name" style="display: inline; width: 200px !important; margin-right: 5px"/>' +
        '<button type="button" class="btn btn-xs add_new_role" onclick="test_add()"><span class="glyphicon glyphicon-plus"></span></button> ' +
        '<button type="button" class="btn btn-xs remrole" ><span class="glyphicon glyphicon-minus"></span></button>' +
        '</p>';
    jQuery(wrapper1).find('.remrole').on('click', function() {
        jQuery(this).parent('.role_p').remove();
    });
    jQuery(wrapper1).appendTo('#p_role');
}

