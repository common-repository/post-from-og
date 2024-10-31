<?php
/* Plugin Name: Post from Open Graph
 * Description: Enter a URL and, if the page has Open Graph information, you can publish a post wiht it. The OG image will be downloaded to your site and set as Featured Image of the new post.
 * Plugin URI: #
 * Version:     1.0
 * Author:      Rodolfo Buaiz
 * Author URI:  http://rodbuaiz.com
 * Text Domain: pfogloc
 * Domain Path: /languages
 * License: GPLv2 or later
 *
 * 
 * This program is free software; you can redistribute it and/or modify it 
 * under the terms of the GNU General Public License version 2, 
 * as published by the Free Software Foundation.  You may NOT assume 
 * that you can use any other version of the GPL.
 *
 * This program is distributed in the hope that it will be useful, 
 * but WITHOUT ANY WARRANTY; without even the implied warranty 
 * of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

add_action(
	'plugins_loaded',
	array ( B5F_Post_From_Og::get_instance(), 'plugin_setup' )
);

class B5F_Post_From_Og
{
	/**
	 * Plugin instance.
	 * @see get_instance()
	 * @type object
	 */
	protected static $instance = NULL;
	
	/**
	 * Plugin option name
	 * @type string
	 */
	public static $opt_name = 'post_from_og';
	
	/**
	 * Plugin version
	 * @type string
	 */
	public static $version = '1.0';
	
	/**
	 * Plugin file name
	 * @type string 
	 */
	public $slug;

	/**
	 * Plugin URL.
	 * @type string
	 */
	public $plugin_url = '';

	/**
	 * Directory path.
	 * @type string
	 */
	public $plugin_path = '';
		
	/**
	 * Used to translate the description in plugins screen.
	 * @type string 
	 */
	private $plugin_description;

	
	/**
	 * Access this plugin’s working instance
	 *
	 * @wp-hook plugins_loaded
	 * @return  object of this class
	 */
	public static function get_instance()
	{
		NULL === self::$instance and self::$instance = new self;

		return self::$instance;
	}

	
	/**
	 * Used for regular plugin work.
	 *
	 * @wp-hook plugins_loaded
	 * @return  void
	 */
	public function plugin_setup()
	{
		/* BASICS */
		$this->plugin_url    = plugins_url( '/', __FILE__ );
		$this->plugin_path   = plugin_dir_path( __FILE__ );
		$this->slug			 = dirname( plugin_basename( __FILE__ ) );
		
		/* LANGUAGE */
		$this->load_language( 'pfogloc' );
		$this->plugin_description = __( 'Enter a URL and, if the page has Open Graph information, you can publish a post wiht it. The OG image will be downloaded to your site and set as Featured Image of the new post.', 'pfogloc' );
		
		/* PLUGIN SETTINGS LINK IN ACTION ROW */
		$plug = plugin_basename( __FILE__ );
		add_filter( 
				"plugin_action_links_$plug", 
				array( $this, 'plugin_action_links' ), 
				10, 2 
		);
		
		/* MENU */
		require_once $this->plugin_path . 'inc/class-settings.php';
		new B5F_Post_From_Og_Settings_Page( self::$opt_name, $this->plugin_url, $this->plugin_description );

		/* META BOX*/
		require_once $this->plugin_path . 'inc/class-meta-box.php';
		new B5F_Post_From_Og_Meta_Box( self::$opt_name, $this->plugin_url );
	}

	
	/**
	 * Constructor. Intentionally left empty and public.
	 *
	 * @see plugin_setup()
	 * @since 2012.09.12
	 */
	public function __construct() {}
	

	
	/**
	 * Add settings link to plugin action row
	 * 
	 * @param array $links
	 * @param string $file
	 * @return array
	 */
	public function plugin_action_links( $links, $file ) 
	{
		$in = sprintf(
				'<a href="%s">%s</a>',
				admin_url( 'index.php?page=post-from-og' ),
				__( 'Create', 'pfogloc' )
		);
		array_unshift( $links, $in );
			
	    return $links;
	} 
	
	
	/**
	 * Loads translation file.
	 *
	 * Accessible to other classes to load different language files (admin and
	 * front-end for example).
	 *
	 * @wp-hook init
	 * @param   string $domain
	 * @since   2012.09.11
	 * @return  void
	 */
	public function load_language( $domain )
	{
        $locale = apply_filters( 'plugin_locale', get_locale(), $domain );
		$mo = WP_LANG_DIR . '/plugins/'.$this->slug . '/' . $domain . '-' . $locale . '.mo';
        load_textdomain( $domain, $mo );
		load_plugin_textdomain(
			$domain,
			FALSE,
			$this->slug . '/languages'
		);
	}
	
	
}