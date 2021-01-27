<?php

// If this file is called directly, abort.
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/**
 * The plugin's admin-specific functionality.
 */
class Podigee_feedex_Admin {
	private $plugin_name;
	private $plugin_version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->plugin_version = $version;
		add_action( 'add_meta_boxes', array($this, 'pfex_add_custom_box'));
		add_action( 'admin_enqueue_scripts', array( $this, 'pfex_enqueue_styles' ));
		add_action( 'admin_enqueue_scripts', array( $this, 'pfex_enqueue_scripts' ));
	}

	/**
	 * Registering the necessary stylesheets.
	 */
	public function pfex_enqueue_styles() {
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'podigee-qp-admin.css', array(), $this->plugin_version, 'all' );
	}

	/**
	 * Registering the necessary scripts.
	 */
	public function pfex_enqueue_scripts() {
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'podigee-qp-admin.js', array( 'jquery' ), $this->plugin_version, false );	
	}

	/**
	 * Adds a custom box to the Wordpress post editor in the admin area.
	 */
	public function pfex_add_custom_box() {
	    $screens = get_post_types();
	    foreach ($screens as $screen) {
	        add_meta_box(
	            'pfex-box-id',           				// Unique ID
	            'Podigee Quick Publish',  	// Box title
	            array($this, 'pfex_custom_box_html'),   // Content callback, must be of type callable
	            $screen                   				// Post type
	        );
	    }
	}

	/**
	 * The HTML code for the custom box in the Wordpress post editor.
	 * This is just here to not confuse people used to version 0.7 and below.
	 */
	public function pfex_custom_box_html($post) {
		_e('Hey, you! Yes, you, the one looking for the magic Podigee content import buttons. We got news for you: we moved everything over to', 'podigee-quick-publish');
		printf(' <a href="admin.php?page=podigee-wpqp-plugin">%s.</a>', __('this page', 'podigee-quick-publish'));
	}
}
