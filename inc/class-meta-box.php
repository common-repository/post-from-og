<?php

class B5F_Post_From_Og_Meta_Box
{
	private $opt_name;
	private $plugin_url;

	public function __construct( $options, $url )
	{
		$this->opt_name = $options;
		$this->plugin_url = $url;

		add_action( 
				'add_meta_boxes', 
				array( $this, 'add_custom_box' ) 
		);
	}


	function add_custom_box()
	{
		global $post;
		if( !$post )
			return;
		if( !get_post_meta( $post->ID, 'pfog_post', true ) )
			return;

		add_meta_box(
				'pfog_post', 
				'Post from OG', 
				array( $this, 'inner_custom_box' ), 
				$post->post_type, 
				'side'
		);
	}


	/* Prints the box content */

	function inner_custom_box( $post )
	{
		$meta_data = get_post_meta( $post->ID, 'pfog_post', true );
		if( !$meta_data )
			return;

		echo '<table class="form-table">';

		$count = 0;
		foreach( $meta_data as $k => $v )
		{
			$class = ( $count % 2 == 0 ) ? ' class="alternate"' : '';
			if( 'twitter' == $k )
				printf(
						'<tr valign="top"%1$s>
					<td scope="row">%2$s</td>
					<td><a href="http://twitter.com/%3$s" target="_blank">%3$s</a></td>
				</tr>', $class, $k, $v
				);
			else
				printf(
						'<tr valign="top"%1$s>
					<td scope="row">%2$s</td>
					<td><input type="text" name="%2$s" value="%3$s" readonly /></td>
				</tr>', $class, $k, $v
				);
		}
		echo '</table>';
	}


}