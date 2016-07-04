/*================================================================================
    OMOP - Cloud Research Lab

    Observational Medical Outcomes Partnership
    3 Dec 2010

    Central Admin menu JS code.

    (c)2009-2010 Foundation for the National Institutes of Health (FNIH)

    Licensed under the Apache License, Version 2.0 (the "License"), you may not
    use this file except in compliance with the License. You may obtain a copy
    of the License at http://omop.fnih.org/publiclicense.

    Unless required by applicable law or agreed to in writing, software
    distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
    WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. Any
    redistributions of this work or any derivative work or modification based on
    this work should be accompanied by the following source attribution: "This
    work is based on work by the Observational Medical Outcomes Partnership
    (OMOP) and used under license from the FNIH at
    http://omop.fnih.org/publiclicense.

    Any scientific publication that is based on this work should include a
    reference to http://omop.fnih.org.

================================================================================*/
var current_submenu = false;

$(document).ready(function(){
    var menu_delay = 300;
    $("#menu_m").hover(function(){
            current_submenu = "submenu_m";
            update_submenus();
    }, function(){
            current_submenu = false;
            setTimeout(update_submenus, menu_delay);
    });
    $("#menu_q").hover(function(){
            current_submenu = "submenu_q";
            update_submenus();
    }, function(){
            current_submenu = false;
            setTimeout(update_submenus, menu_delay);
    });
    $("#menu_d").hover(function(){
            current_submenu = "submenu_d";
            update_submenus();
    }, function(){
            current_submenu = false;
            setTimeout(update_submenus, menu_delay);
    });

    $("#submenu_m").hover(function(){
            current_submenu = "submenu_m";
    }, function(){
            current_submenu = false;
            setTimeout(update_submenus, menu_delay);
    });
    $("#submenu_q").hover(function(){
            current_submenu = "submenu_q";
    }, function(){
            current_submenu = false;
            setTimeout(update_submenus, menu_delay);
    });
    $("#submenu_d").hover(function(){
            current_submenu = "submenu_d";
    }, function(){
            current_submenu = false;
            setTimeout(update_submenus, menu_delay);
    });

    $('.check-all').click(function() {
        $('.all-control .links').slideToggle(200);
    });

    $('.all-control .links span').click(function() {
        var checkboxes = $(this).closest('table').find('[type="checkbox"]:enabled');
        var operation = $(this).data('check');
        if (operation === true) {
            checkboxes.attr('checked', 'checked');
        } else if (operation === 'toggle') {
            checkboxes.each(function() { this.checked = !this.checked });
        } else {
            checkboxes.removeAttr('checked');
        }
        $(this).closest('.links').hide();
    });

});

function update_submenus ()
{
    if (current_submenu == "submenu_m")
    {
        $("#submenu_m").show();
        $("#submenu_q").hide();
        $("#submenu_d").hide();
    }
    else if (current_submenu == "submenu_q")
    {
        $("#submenu_m").hide();
        $("#submenu_q").show();
        $("#submenu_d").hide();
    }
    else if (current_submenu == "submenu_d")
    {
        $("#submenu_m").hide();
        $("#submenu_q").hide();
        $("#submenu_d").show();
    }
    else
    {
        $("#submenu_m").hide();
        $("#submenu_q").hide();
        $("#submenu_d").hide();
    }
}