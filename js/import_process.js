
 function get_form_elements(parent){
    var all_elements = {};
    jQuery('input,select,textarea', parent).each(
        function(k){
        all_elements[jQuery(this).attr('name')] = jQuery(this).attr('value');
    });
    
    // });
    return all_elements;
}

function printObject(o, level) {
    var out = '';
    if(level==null) level = 1;
    for (var p in o) {
        // if(typeof o == 'object') {
        //     printObject(o, level+1);
        // } else {
        //     for (var i = level - 1; i >= 0; i--) {
        //         out += '- ';
        //     };
            out += p + ': ' + o[p] + '<br>';
        //}
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
    // product select 
    var config = {
      '.chosen-select'           : {},
    }

    jQuery('#kick_file_upload').ajaxForm({
        url:  ajaxurl,
        type: 'POST',
        success: function(response){
            //jQuery('.woo_kick_response').append('<br>submit page 1');
            //jQuery('.woo_kick_response').append(response);
            response = jQuery.parseJSON(response);
            if( response.error ) {
                jQuery('.woo_kick_response').html(response.msg).addClass('error');
            } else {
                jQuery.post(ajaxurl, response, function(page2){
                    jQuery('.woo_kick_response').html('').removeClass('error');
                    jQuery('.woo_kick_stage').html(page2);
                    for (var selector in config) {
                      jQuery(selector).chosen(config[selector]);
                    }
                });
            }
            //jQuery(document).ready();
            jQuery('.woo_kick_response').html('').removeClass('error');
        }
    });



    jQuery(document).live('submit', '#kick_file_define', function(){

        jQuery('#kick_file_define').ajaxSubmit({
            url:  ajaxurl,
            type: 'POST',
            beforeSubmit: function(arr, bf, options){
                //product_choices
                //jQuery('.woo_kick_response').append('before:'+jQuery('#product_choices').chosen().val());
                //return false;

            },
            success: function(response){
                try {
                    response = jQuery.parseJSON(response);
                } 
                catch (error){
                     jQuery('.woo_kick_response').html(response);
                    return false;
                }
                
                if( response.error ) {
                    jQuery('.woo_kick_response').html(response.msg).addClass('error');
                } else {
                    //jQuery('.woo_kick_response').append('ALL IS GOOD<br>'+response);
                    jQuery.post(ajaxurl, response, function(page3){
                        jQuery('.woo_kick_response').html('').removeClass('error');
                        jQuery('.woo_kick_stage').html(page3);
                    });
                }
            },
            error: function(e){
                jQuery('.woo_kick_response').html('error'+e);
            }
        });
        return false;
    });

    jQuery('.next_action').on('click', function(){
        //jQuery('.woo_kick_response').append('<br>next action');
        var response = {
            action: 'kickstarter_define_page',
            file: '\/var\/www\/html\/wp-content\/uploads\/survery_import.csv'
        };
        jQuery.post(ajaxurl, response, function(page){
            jQuery('.woo_kick_stage').html(page);
            for (var selector in config) {
              jQuery(selector).chosen(config[selector]);
            }
        });
    });
});
   
  
    
