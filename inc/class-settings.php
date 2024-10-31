<?php

class B5F_Post_From_Og_Settings_Page
{
	/**
	 * Menu ID, used to enqueue in correct page
	 * @var string 
	 */
	private $menu_id;

	private $opt_name;
	private $plugin_url;
	private $plugin_desc;
	
	
	public function __construct( $options, $url, $desc )
	{
		$this->opt_name = $options;
		$this->plugin_url = $url;
		$this->plugin_desc = $desc;
		
		add_action( "admin_menu", array( $this, 'settings_menu' ) );
		add_action( 'wp_ajax_query_external_site', array( $this, 'query_external_site' ) );
//		add_action( 'wp_ajax_nopriv_query_external_site', array( $this, 'query_external_site' ) );
		add_action( 'wp_ajax_create_post', array( $this, 'create_post' ) );
//		add_action( 'wp_ajax_nopriv_create_post', array( $this, 'create_post' ) );
		add_action( "admin_print_scripts", array( $this, 'fix_mp6_bug' ), 9999 );
	}
	
	
	public function fix_mp6_bug()
	{
		if( function_exists( 'mp6_register_admin_color_schemes' ) ) {
			$url = admin_url('images/');
			echo "<style>.icon32.icon-post, #icon-post{background-position: -552px -5px !important;background-image: url({$url}icons32.png) !important; display:block} td.row-title{ width:100%</style>";
//			echo "<style>}</style>";
		}
	}
	
    /**
     * Add submenu item, no API
     * 
	 * Other menus:
	 * add_dashboard_page, add_posts_page, add_media_page, add_links_page, 
	 * add_pages_page, add_comments_page, add_theme_page, add_plugins_page, 
	 * add_users_page, add_management_page, add_options_page
	 * 
     * @return void
     */
    public function settings_menu()
    {
        $this->menu_id = add_dashboard_page(
                __( 'Post from OG', 'pfogloc' )
                , __( 'Post from OG', 'pfogloc' )
                , 'edit_posts'
                , 'post-from-og'
                , array( $this, 'render_settings_page' )
        );
		add_action(
				"admin_print_scripts-{$this->menu_id}", 
						array( $this, 'enqueue_backend' ) 
		);
    }


	/**
	 * Enqueue script and style.
	 * 
	 * @wp-hook admin_print_scripts
	 * @return array
	 */
	public function enqueue_backend()
	{
		$this->fix_mp6_bug();
		$cache = ( '127.0.0.1' == $_SERVER['REMOTE_ADDR'] ) ? time() : false;
		wp_enqueue_script( 
				'pfog-js-back', 
				$this->plugin_url . 'js/post-from-og.js', 
				array(), 
				$cache, 
				true 
		);
		wp_localize_script( 
				'pfog-js-back', 
				'wp_ajax', 
				array( 
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
					'ajaxnonce' => wp_create_nonce( 'ajax_pfog_validation' )
				) 
		); 
        wp_enqueue_style( 
				'pfog-css-back', 
				$this->plugin_url . 'css/post-from-og.css',
				array(),
				$cache
		);
	}


	function create_drop_down( $type )
	{
		if( 'cpt' == $type )
		{
			$args = array( 'public' => true, 'show_ui' => true ); 
			$options = get_post_types( $args );
			unset( $options['attachment'] );			
		}
		else
		{
			$options = array( 'draft', 'publish', );
			
		}
		$saved = get_option( B5F_Post_From_Og::$opt_name );
		$saved[ $type ] = isset( $saved[ $type ] ) ? $saved[ $type ] : '';
		
		$id = "pfog_{$type}_list";
		$re = "<select name='$id' id='$id'>";
		foreach( $options as $dd )
		{
			$sel = selected( $saved[ $type ], $dd, false);
			$re .= '<option value="' . $dd . '" ' . $sel. '>' . __( ucwords( $dd ) ) . '</option>';
		}
		$re .= '</select>';

		return $re;
	}

	/**
     * Settings page, hand made, no API
     * 
     * @return Html content
     */
    public function render_settings_page()
    {
        ?>
        <div class="wrap">
			<br />		
			<div class="postbox">	
				<h2><div id="icon-post" class="icon32"></div><?php _e( 'Create Post from Open Graph', 'pfogloc' ); ?></h2>
				<p class="desc"><?php echo $this->plugin_desc; ?></p>
				<div class="inside">
					<table class="widefat" cellspacing="0">
						<tbody><tr>
							<td class="row-title">
								
								<ul id="pfog-url-input">
									<li><img src="<?php echo $this->plugin_url.'images/transparent.gif'; ?>" width="1" height="50"></li>
									<li class="inputtext"><input type='text' id='get_that_site_page_url' name='get_that_site_page_url' value='' /></li>	
									<li class="geturl"><input type="submit" name="get_that_site_page_button" id="get_that_site_page_button" 
											   class="button-primary" 
											   value="<?php _e( 'Grab site info', 'pfogloc' ); ?>" />
										<a href="#" id="clear-url"></a></li>
									<li class="resp"><div id="loading-gif"></div><div id="pfog-error"></div></li>
								</ul>
								<ul id="pfog-create-post"><li></li>
									<li class="create_that_post_holder" style="display:none"><?php echo $this->create_drop_down('cpt'); ?></li>
									<li class="create_that_post_holder" style="display:none"><?php echo $this->create_drop_down('status'); ?></li>
									<li class="create_that_post_holder" style="display:none"><label><input type="checkbox" name="dont_redirect" id="dont_redirect" /> <?php _e( "Don't redirect after post creation.", 'pfogloc' ); ?></label></li>
									<li class="create_that_post_holder" style="display:none">
										<input type="submit" 
											   name="create_that_post_button"  
											   id="create_that_post_button"
											   class="button-secondary button-secondary-pfog" 
											   value="<?php _e( 'Create a post', 'pfogloc' ); ?>" /></li>
									
								</ul>
								
							</td>
						</tr>
						<tr class="alternate" id="pfog-response" style="display:none">
							<td class="row-title">
								<div>
									<ul>
										<li id="og-site_name"><span class="desc"></span></li>
										<li id="og-type"><span class="desc"></span></li>
										<li id="og-locale"><span class="desc"></span></li>
										<li id="og-title"><a href="#"><span class="desc"></span></a></li>
										<li id="og-description"><span class="desc"></span></li>
										<li id="og-image"><span class="desc"></span></li>
									</ul>
								</div>
							</td>
						</tr>
						<tr><td class='credits credits-text'><?php printf( '%s %s', __( 'Version' ), B5F_Post_From_Og::$version ); ?></td>
							<td class='credits credits-text credits-rod'>by <a href='http://rodbuaiz.com'>Rodolfo Buaiz</a>
							</td>
						</tr>
					</tbody></table>		
				</div> <!-- .inside -->
			</div>
			
			
		</div>
            
        <?php 
    }


	
	function query_external_site()
	{
		check_ajax_referer( 'ajax_pfog_validation', 'ajaxnonce' );
		
		$response = wp_remote_get( esc_url( $_POST['pfog_url'] ), array( 'timeout' => 120, 'httpversion' => '1.1' ) );

		if ( is_wp_error( $response ) )
		{
			$error = $response->get_error_message();
			wp_send_json_error( array( 'error' => $error ) );
		}
		else
		{
			if ( $data = wp_remote_retrieve_body( $response ) )
			{
				
				libxml_use_internal_errors(true); 
				$doc = new DomDocument();
				
				$doc->loadHTML($data);
				//
				$xpath = new DOMXPath($doc);

				$query = '//*/meta[starts-with(@property, \'og:\')]';
				$metas = $xpath->query($query);
				foreach ($metas as $meta) {
					$property = $meta->getAttribute('property');
					$content = $meta->getAttribute('content');
					$rmetas[$property] = $content;
				}
				
				if( empty($rmetas) )
				{	
					wp_send_json_error( array(
						'error' => __( 'No OG data in the page.', 'pfogloc' ) 
					));
				}	
				
				/* Meta Data for the post */
				if( $val = $this->xpath_query( $xpath, 'meta', 'name', 'author', 'content' ) )
					$rmetas['author'] = $val;	
				if( $val = $this->xpath_query( $xpath, 'meta', 'name', 'dc.publisher', 'content' ) )
					$rmetas['author'] = $val;	
				if( $val = $this->xpath_query( $xpath, 'meta', 'name', 'dc.date', 'content' ) )
					$rmetas['date'] = $val;	
				if( $val = $this->xpath_query( $xpath, 'link', 'rel', 'shorturl', 'href' ) )
					$rmetas['shorturl'] = $val;	
				if( $val = $this->xpath_query( $xpath, 'meta', 'property', "article:author", 'content' ) )
					$rmetas['authorurl'] = $val;	

				$metas = $xpath->query('//*/meta[starts-with(@property, \'twitter:\')]');
				foreach ($metas as $meta) {
					$property = $meta->getAttribute('property');
					$content = $meta->getAttribute('value');
					if( 'twitter:site' == $property )
						$rmetas['twitter'] = $content;	
				}
				
				wp_send_json_success( $rmetas );
			}
		}
		wp_send_json_error( array(
			'error' => __( 'Undefined error.', 'pfogloc' ) 
		));
	}
	
	
	private function xpath_query( $xpath, $loc, $type, $value, $what )
	{
		$contents = $xpath->query('/html/head/'.$loc.'[@'.$type.'="'.$value.'"]/@'.$what);
		
		if ($contents->length != 0) 
		{
			foreach ($contents as $content) {
				return $content->value;
			}
		}
		return false;
	}
	
	
	function create_post()
	{
		check_ajax_referer( 'ajax_pfog_validation', 'ajaxnonce' );

		if( !isset( $_POST['og_info'] ) || !isset( $_POST['og_info']['og:title'] )) {
			wp_send_json_error( array(
				'error' => __( 'Title not set.', 'pfogloc' ) 
			));
		}
		
		// Prepare contents
		$cpt = isset( $_POST['og_cpt'] ) ?  $_POST['og_cpt'] : 'post';
		$status = isset( $_POST['og_status'] ) ?  $_POST['og_status'] : 'draft';
		
		
		$update_option['cpt'] = $cpt;
		$update_option['status'] = $status;	
		update_option( B5F_Post_From_Og::$opt_name, $update_option );
		
		$desc = isset( $_POST['og_info']['og:description'] ) ? $_POST['og_info']['og:description'] : '';
		
		$add_post = array(
						'post_title'   => stripslashes( wp_specialchars_decode( $_POST['og_info']['og:title'] ) ),
						'post_content' => stripslashes( wp_specialchars_decode( $desc ) ),
						'post_status'  => $status,
						'post_type'    => $cpt
					  );
		
		// insert the post into the database
		$inserted_post = wp_insert_post( $add_post, true );
		
		if( is_wp_error( $inserted_post ) )
		{
			wp_send_json_error( array(
				'error' => $inserted_post->get_error_message()
			));
		}
		
		// Thumb title
		$thumb_title = isset( $_POST['og_info']['og:site_name'] )
				? $_POST['og_info']['og:site_name']
				: $_POST['og_info']['og:title'];
		
		// Create image if exists
		if( isset( $_POST['og_info']['og:image'] ) )
			$this->set_thumb_by_url( 
					$_POST['og_info']['og:image'], 
					stripslashes( wp_specialchars_decode( $thumb_title ) ), 
					$inserted_post
			);
		
		$this->set_post_meta( $_POST['og_info'], $inserted_post );
		
		wp_send_json_success( 
				admin_url( "post.php?post=$inserted_post&action=edit" )
		);
	}
	
	
	private function set_post_meta( $POST, $pid )
	{
		$meta_data = array();
		if( isset( $POST['og:type'] ) )
			$meta_data['type'] = $POST['og:type'];
		if( isset( $POST['og:locale'] ) )
			$meta_data['locale'] = $POST['og:locale'];
		if( isset( $POST['og:video'] ) )
			$meta_data['video'] = $POST['og:video'];
		if( isset( $POST['author'] ) )
			$meta_data['author'] = $POST['author'];
		if( isset( $POST['twitter'] ) )
			$meta_data['twitter'] = $POST['twitter'];
		if( isset( $POST['date'] ) )
			$meta_data['date'] = $POST['date'];
		if( isset( $POST['shorturl'] ) )
			$meta_data['shorturl'] = $POST['shorturl'];
		
		if( !empty( $meta_data ) )
			update_post_meta( $pid, 'pfog_post', $meta_data );		
	}
	
	
	/**
     * Attempt to download the image from the URL, add it to the media library,
     * and set as the featured image.
     *
     * @author http://wordpress.stackexchange.com/a/71629/12615
     *
     * @param string $url
     * @param string $title Optionally set attachment title
     */
    private function set_thumb_by_url( $url, $title = null, $post_id )
    {
        $temp = download_url( $url );

        if ( ! is_wp_error( $temp ) && $info = @ getimagesize( $temp ) ) {
            if ( ! strlen( $title ) )
                $title = null;

            if ( ! $ext = image_type_to_extension( $info[2] ) )
                $ext = '.jpg';

            $data = array(
                'name'     => md5( $url ) . $ext,
                'tmp_name' => $temp,
            );

            $id = media_handle_sideload( $data, $post_id, $title );
            if ( ! is_wp_error( $id ) )
                return update_post_meta( $post_id, '_thumbnail_id', $id );
        }

        if ( ! is_wp_error( $temp ) )
            @ unlink( $temp );
    }

	
}