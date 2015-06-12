
 function get_form_elements(parent){
    var all_elements = {};
    jQuery('input,select,textarea', parent).each(
        function(k){
        all_elements[jQuery(this).attr('name')] = jQuery(this).attr('value');
    });
    
    // });
    return all_elements;
}

function printObject(o) {
    var out = '';
    for (var p in o) {
    out += p + ': ' + o[p] + '<br>';
    }
    return out;
}

// jQuery(document).ready(function(){
//     jQuery('.kick_survey_import').click(function(){
//         //jQuery('.woo_kick_response').html('good');
//         var data = get_form_elements('#kick_file_upload');
//         //var data = jQuery(this).parents('form').serialize();
//         jQuery('.woo_kick_response').append('sending:'+printObject(data));
//         jQuery.post( ajaxurl, data, function(response){
//             jQuery('.woo_kick_response').append('<br>finished:<pre>'+response);
//         });
//     });
// });

jQuery(document).ready(function(){
    jQuery('#kick_file_upload').ajaxForm({
        url:  ajaxurl,
        type: 'POST',
        success: function(response){
           //jQuery('.woo_kick_response').html(response);
            response = jQuery.parseJSON(response);
            
            if( response.error ) {
                jQuery('.woo_kick_response').html(response.msg).addClass('error');
            } else {
                //jQuery('.woo_kick_response').append('<br>finished:<pre>'+printObject(response));
                //jQuery('.woo_kick_stage').html(response.kicksteppage);
                
                jQuery.post(ajaxurl, response, function(page2){
                    jQuery('.woo_kick_stage').html(page2);
                });
            }
        }
    });

    // product select 
    var config = {
      '.chosen-select'           : {},
    }
    for (var selector in config) {
      jQuery(selector).chosen(config[selector]);
    }
});




