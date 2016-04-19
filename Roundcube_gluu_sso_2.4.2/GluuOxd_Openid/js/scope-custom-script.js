/**
 * Created by Vlad on 3/20/2016.
 */
jQuery(document).ready(function() {
    jQuery('#adding').click(function() {
        var wrapper = "<tr class='wrapper-tr'>" +
            "<td class='value'><input type='text' placeholder='Input scope name' name='scope_name[]'></td>" +
            "<td class='value'><button class='remove'>Remove</button></td>" +
            "</tr>";
        jQuery(wrapper).find('.remove').on('click', function() {
            jQuery(this).parent('.wrapper-tr').remove();
        });
        jQuery(wrapper).appendTo('.form-list5');
    });
    jQuery('.form-list5').on('click', 'button.remove', function() {
        if (jQuery('.wrapper-tr').length > 1) {
            jQuery(this).parents('.wrapper-tr').remove();
        } else {
            alert('at least one image need to be selected');
        }
    });

    var j = jQuery('.count_scripts').length + 1;
    var d = jQuery('.count_scripts').length + 1;
    jQuery('#adder').click(function() {
        var wrapperer = "<tr class='count_scopes wrapper-trr'>" +
            "<td  class='value'><input style='width: 200px !important;' placeholder='Display name (example Google+)' type='text' name='name_in_site_"+j+"'></td>" +
            "<td  class='value'><input style='width: 270px !important;' placeholder='ACR Value (script name in the Gluu Server)' type='text' name='name_in_gluu_"+j+"'></td>" +
            "<td class='value'><input type='file' accept='image/png' name='images_"+j+"'></td>" +
            "<td class='value'><button class='removeer'>Remove</button></td>" +
            "</tr>";
        jQuery(wrapperer).find('.removeer').on('click', function() {
            jQuery(this).parent('.wrapper-trr').remove();

        });
        jQuery('#count_scripts').val(d);
        j++;
        d++;

        jQuery(wrapperer).appendTo('.form-list1');

    });
    jQuery('.form-list1').on('click', 'button.removeer', function() {
        if (j > 2) {
            jQuery(this).parents('.wrapper-trr').remove();
            j--;
        }
    });

    jQuery("#show_script_table").click(function(){
        jQuery("#custom_script_table").toggle();
    });
});

