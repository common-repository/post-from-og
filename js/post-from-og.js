jQuery(document).ready(function($)
{
    /* Grab OG result */
    var arr_from_json;
    
    
    /* URL */
    var origin_url;

    
    /* Focus our input field */
    $('#get_that_site_page_url').focus();
    
    
    /* CLEAR FILDS BUTTON */
    $('#clear-url').click(function()
    {
        b5f_clear_og();
        $('#get_that_site_page_url').val('');
    });
    
    
    /* KEYBOARD LISTENER - FIRES SEARCH BUTTON */
    $("#get_that_site_page_url").keyup(function (e) {
        if (e.keyCode == 13) 
            $('#get_that_site_page_button').click();
    });
    
    
    /**
     * Handle loading.gif visibility
     * 
     * @param string how_what
     * @returns void
     */
    function b5f_ajax_loading( how_what )
    {
        if( 'hide' == how_what )
            $('#loading-gif').fadeTo( 30, 0, function(){ 
                $('#loading-gif').removeClass( 'loading-gif-bg' ) 
            });
        else 
            $('#loading-gif').fadeTo( 500, 1, function(){ 
                $('#loading-gif').addClass( 'loading-gif-bg' ); 
            });
    }
    
    
    /**
     * Handle error messages
     * 
     * @param string how_what
     * @returns void
     */
    function b5f_handle_error( how_what )
    {
        if( 'hide' === how_what ) {
            $('#pfog-error').fadeTo( 30,0,function(){$('#pfog-error').html( '' );} );
        }
        else {
            $('#pfog-error').html( how_what ).fadeTo( 500,1,function(){} );
//            $('#pfog-error').html( how_what );
        }
    }
    
    
    /**
     * Print OG results
      * 
     * @param array arr
     * @param string url
     * @returns void
     */
    function b5f_print_og( arr, url )
    {
        var re = /:/gi; // replace all ocurrences, http://goo.gl/OPirW
        for( var k in arr )
        {
            og = k.replace(re, "-");

            if( 'og-image' == og )
                echo = '<img src="' + arr[ k ] + '">';
            else if( 'og-title' == og )
            {
                $('#og-title a').attr( 'href', url );
                echo = arr[ k ];
            }
            else
                echo = arr[ k ];
            
            $('#' + og + ' .desc').html( echo );
        }

        $('#pfog-response, .create_that_post_holder').show();
        
        $('#get_that_site_page_button').hide();
        $('#clear-url').addClass( 'close-button-bg' );
    }
    
    
    /**
     * Clear OG results
     */
    function b5f_clear_og()
    {
        $('#pfog-response span.desc').html('');
        $('.create_that_post_holder').hide();
        $('#pfog-response').hide();
        
        $('#get_that_site_page_button').show();
        $('#clear-url').removeClass( 'close-button-bg' );
        $('#get_that_site_page_url').focus();
    }
    
    
    /**
     * Grab OG Ajax response
     * 
     * @param object response
     * @returns void
     */
    function b5f_grab_response( response )
    {
        b5f_ajax_loading( 'hide' );
        
        // Error
        if( !response.success )
        {
            b5f_handle_error( response.data.error );
            b5f_ajax_loading( 'hide' );
            return;
        }

        arr_from_json = response.data;
        b5f_print_og( arr_from_json, origin_url );
        
    }
    
    

    /**
     * Grab OG information from URL
     */
    $('#get_that_site_page_button').click(function()
    {
        b5f_ajax_loading();
        b5f_handle_error( 'hide' );
        b5f_clear_og();
        
        origin_url = $('#get_that_site_page_url').val();
        
        /* Grab OG data */
        var data = {
            action: 'query_external_site',
            ajaxnonce: wp_ajax.ajaxnonce,
            pfog_url: origin_url
        };
        
        $.post( wp_ajax.ajaxurl, data, b5f_grab_response );
    }); 


    /**
     * Publish post Ajax response
     * 
     * @param object response
     * @returns string
     */
    function b5f_post_response( response )
    {
        if( !response.success )
        {
            b5f_ajax_loading( 'hide' );
            b5f_handle_error( response.data.error );
            return;
        }
        if( $('#dont_redirect').attr('checked') )
        {
            b5f_handle_error( '<a href="' + response.data + '">View post</a>' );
            b5f_ajax_loading( 'hide' );
            b5f_clear_og();
            $('#get_that_site_page_url').val('');
        }
        else
            window.location.href = response.data;                  
    }

    /**
     * Create Post from OG result
     */
    $('#create_that_post_button').click(function()
    {
        b5f_ajax_loading();
        b5f_handle_error( 'hide' );
        
        var data_post = {
            action: 'create_post',
            ajaxnonce: wp_ajax.ajaxnonce,
            og_info: arr_from_json,
            og_cpt: $('#pfog_cpt_list').val(),
            og_status: $('#pfog_status_list').val()
        };

        $.post(
                wp_ajax.ajaxurl,
                data_post,
                b5f_post_response
        );
    }); 
    
});