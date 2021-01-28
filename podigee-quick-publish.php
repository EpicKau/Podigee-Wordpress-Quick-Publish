<?php
/**
* Plugin Name: Podigee Wordpress Quick Publish – now with Gutenberg support!
* Plugin URI:  https://podigee.com
* Description: Let's you import metadata from your Podigee podcast feed right into the Wordpress post editor. Now also compatible to Gutenberg. Developed for Podigee by Jürgen Krauß (https://www.es-ist-ein-krauss.de/).
* Text Domain: podigee-quick-publish
* Version:     1.1
* Author:      Podigee
* Author URI:  https://podigee.com
* License:     MIT
Copyright (c) 2020 Podigee
Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

/*
* We use too global variables in this plugin to reduce requests to our authentication service and the Wordpress database. If you have an idea that needs even less requests, let me know! ;-)
*/

$_PFEX_LOGIN_OKAY;
$_PFEX_POST_INSERTED;
$_PFEX_DEBUG = (isset($_GET['pfex-debug']) && $_GET['pfex-debug'] == "1" ? true : false);

// If this file is called directly, abort.
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/**
 * The (admin-only) core plugin class.
 */
require plugin_dir_path( __FILE__ ) . 'admin/class-podigee-qp.php';

/**
 * Initializing and startin the plugin.
 */
function run_podigee_feedex() {
	$plugin_admin = new Podigee_feedex_Admin('PODIGEE_WORDPRESS_QUICK_PUBLISH', '1.0.0');
}
run_podigee_feedex();

/**
 * Registering the shortcode for the Podigee audio player.
 */
if (!(function_exists('podigee_player'))) { function podigee_player( $atts ) {
		$atts = shortcode_atts(
			array(
				'url' => '',
			),
			$atts
		);
		/**
		* From the documentation (see: https://github.com/podigee/podigee-podcast-player#usage):
		* "By default the player is integrated into the page using a <script> HTML tag. This is necessary to render the player in an iframe to ensure it 
		* does not interfere with the enclosing page's CSS and JS while still being able to resize the player interface dynamically."
		*/
		return '<script class="podigee-podcast-player" src="https://cdn.podigee.com/podcast-player/javascripts/podigee-podcast-player.js" data-configuration="' . esc_url($atts['url']) . '/embed?context=external"></script>';
	}
	if (!(shortcode_exists("podigee-player"))) add_shortcode( 'podigee-player', 'podigee_player' );
}
/* 
* Preparing translation
*/
function pfex_load_plugin_textdomain() {
    load_plugin_textdomain( 'podigee-quick-publish', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'pfex_load_plugin_textdomain' );

/**
 * Registering an options page in the admin menu.
 */
function pfex_plugin_admin_add_page() {
	add_menu_page( 'Podigee Wordpress Quick Publish', 'Podigee', 'manage_options', 'podigee-wpqp-plugin', 'pfex_plugin_options_page', 'dashicons-megaphone' );
}
add_action('admin_menu', 'pfex_plugin_admin_add_page');

/**
 * This is the main funtion that draws the Podigee options page in the Wordpress admin backend.
 */
function pfex_plugin_options_page() {
	/**
	* Always display headline and top logo
	*/
	_e('<h1 class="pfex-site-title">Podigee Wordpress Quick Publish</h1> <span class="pfex-on-an-additional-note">(now Gutenberg-compatible!)</span>', 'podigee-quick-publish');
	pfex_plugin_section_head();	

	/**
	* If one or more new posts have been saved, povide a message and links to them right on top of the page. 
	* New post ids are stored in an array in $_PFEX_POST_INSERTED
	*/
	global $_PFEX_POST_INSERTED;
	$pfex_backbtn = '<a class="button button-secondary" href="edit.php">';
	$pfex_backbtn .= __('&lt;- back to post overview', 'podigee-quick-publish');
	$pfex_backbtn .= '</a>';
	if (is_array($_PFEX_POST_INSERTED) && count($_PFEX_POST_INSERTED) > 0): 
		echo '<div class="card div-pfex-success"><h2 class="title">';
		_e('Congratulations!', 'podigee-quick-publish');
		echo "</h2><p>";
		
		echo _n(
		        'Your post has been saved as draft:',
		        'Your posts have been saved as drafts:',
		        count($_PFEX_POST_INSERTED),
		        'podigee-quick-publish'
		    );

		echo "</p><p><ul>";
		foreach ($_PFEX_POST_INSERTED as $newpost):
			echo "<li><strong>";
			$queried_post = get_post($newpost);
			echo $queried_post->post_title;
			echo '</strong>:<br /><br /><a class="button button-primary" href="'.get_site_url().'?p='.$newpost.'&preview=true">';
			_e('View it here -&gt;', 'podigee-quick-publish');
			echo '</a> <a class="button button-secondary" href="post.php?post='.$newpost.'&action=edit">';
			_e('Or edit it here -&gt', 'podigee-quick-publish');
			echo "</a><br />&nbsp;</li>";
		endforeach;
		echo "</ul></p>".$pfex_backbtn."</div>";
	elseif (is_string($_PFEX_POST_INSERTED) && substr_count(strtolower($_PFEX_POST_INSERTED), 'error')):
		echo '<div class="card div-pfex-error"><h2 class="title">';
		_e('Whoopsie.', 'podigee-quick-publish');
		echo "</h2><p>";
		_e('While saving your post(s), an error has occured: <br />', 'podigee-quick-publish');
		echo $_PFEX_POST_INSERTED;
		echo "</p>".$pfex_backbtn."</div>";
	endif;

	/**
	* Info section – maybe this can be removed in a future version.
	*/
	pfex_plugin_section_text();
	/**
	* Feed item list.
	*/
	pfex_plugin_section_feeditems();
	

	/**
	* And, finally, the option section:
	*	- Visible when options are not set yet or authentication failed.
	*	- Hidden when authentication was okay.
	* 	– Comes with a jQuery-operated toggle-visibility button.
	*/
	$options = get_option('pfex_plugin_options');
	$auth = check_authorization($options['pfex_slug'], $options['pfex_token']);
	?>
	<h2 class="pfex-subhead"><?php _e('Plugin settings', 'podigee-quick-publish'); ?></h2>
	<button class="button button-secondary pfex-toggle-hidden" data-toggle="<?php if($auth) _e('Hide options', 'podigee-quick-publish'); else _e('Show options', 'podigee-quick-publish'); ?>"><?php if($auth) _e('Show options', 'podigee-quick-publish'); else  _e('Hide options', 'podigee-quick-publish'); ?></button>
	<div class="pfex-option-section <?php if($auth) echo "pfex-hidden"; ?>">
		<form action="options.php" method="post"<?php if(!$auth && (!empty($options['pfex_slug']) || !empty($options['pfex_token'])) ) { ?> class="pfex-auth-error"<?php } ?>>
		<p><?php settings_fields('pfex_plugin_options'); ?>
		<?php do_settings_sections('podigee-wpqp-plugin'); ?>
		 </p><p>
		<input name="Submit" type="submit" class="button button-primary" value="<?php esc_attr_e('Save Changes'); ?>" />
		</p></form>
	</div>
	 
	<?php
}

/*
*	Create database to store feed entrys
*/
function pfex_create_database() {
	
	global $wpdb;
	
	$db_version = "1.0";
	$table_name = $wpdb->prefix . 'pfex_feed';
	$charset_collate = $wpdb->get_charset_collate();
	
	$installed_db_version = get_option("pfex_db_version");
	
	if($installed_db_version != $db_version)
	{
	
		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			link text NOT NULL,
			title text NOT NULL,
			podcast text NOT NULL,
			episodetype text NOT NULL,
			episodenumber text NOT NULL,
			pubdate datetime NOT NULL,
			shortcode text NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";
		
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
		
		if($installed_db_version)
		{
			update_option('pfex_db_version', $db_version);
		} else {
			add_option('pfex_db_version', $db_version);
		}

		add_option('pfex_db_entrys', 0);
	}
	
	
}
register_activation_hook( __FILE__, 'pfex_create_database');

/*
*	Update database
*/
function pfex_update_database($episodes) {
	
	global $wpdb;
	
	$table_name = $wpdb->prefix . 'pfex_feed';
	
	$wpdb->query("TRUNCATE TABLE {$table_name}");
	
	
	foreach($episodes as $episode)
	{
		$wpdb->insert( 
			$table_name, 
			array(
				'link' => $episode['link'],
				'title' => $episode['title'],
				'podcast' => $episode['podcast'],
				'episodetype' => $episode['episodetype'],
				'episodenumber' => $episode['episodenumber'],
				'pubdate' => $episode['pubdate'],
				'shortcode' => $episode['shortcode']
			)
		);
	}
}

/*
*	Get database entrys
*/
function pfex_get_database_entrys() {
	
	global $wpdb;
	
	$table_name = $wpdb->prefix . 'pfex_feed';
	
	$episodes = $wpdb->get_results("SELECT * FROM  {$table_name}", ARRAY_A);
	
	return $episodes;
}


/*
*	Register cron job
*/
function pfex_register_cron() {

	if(!wp_next_scheduled('pfex_feed_update'))
	{
		wp_schedule_event(time(), 'hourly', 'pfex_feed_update');
	}
}
register_activation_hook( __FILE__, 'pfex_register_cron');

/*
*	Deregister cron job
*/
function pfex_deregister_cron() {

	$timestamp = wp_next_scheduled('pfex_feed_update');
	
	wp_unschedule_event ($timestamp, 'pfex_feed_update');
}
register_deactivation_hook( __FILE__, 'pfex_deregister_cron' );

/*
*	Feed update function
*/
function pfex_update_feed() {
	
	
	$options = get_option('pfex_plugin_options');
	
	if(!check_authorization($options['pfex_slug'], $options['pfex_token']))
	{
		return false;
	}
	
	if(isset($options['pfex_slug']) && trim($options['pfex_slug']) != "")
	{
		$subdomains = explode(",", $options['pfex_slug']);
		$episodes = array();
		
		if(count($subdomains) > 0)
		{
			foreach ($subdomains as $subdomain)
			{
				$feed = "https://" . trim($subdomain) . ".podigee.io/feed/mp3/";
				$items = feed2array($feed);
				
				if(count($items) > 0)
				{
					foreach ($items as $episode)
					{
					
					$playershortcode = 'https://'.trim($subdomain).'.podigee.io/'.($episode['episodetype'] != "full" ? substr($episode['episodetype'],0,1) : '').$episode['number'].'-wordpress'; // $_POST['link'];
					$playershortcode = '[podigee-player url="'.$playershortcode.'"]';
					$episodenumber = ($episode['episodetype'] != "full" ? substr($episode['episodetype'],0,1) : '') . $episode['number'];
					$pubdate = date("Y-m-d H:i:s", strtotime($episode['pubDate']));
					
					array_push($episodes, array('link' => $episode['link'], 'title' => $episode['title'], 'podcast' => $subdomain, 'episodetype' => $episode['episodetype'], 'episodenumber' => $episodenumber, 'pubdate' => $pubdate, 'shortcode' => $playershortcode));
					
					}
				}
			}
		}
		
		pfex_update_database($episodes);
	}
}
add_action ('pfex_feed_update', 'pfex_update_feed'); 




/*
* Yeah we know: it's called "post" but actually it is a GET operation (initially it used to be "post").
*/ 
function pfex_handle_post_new($subdomain, $episodenumber) { //$post) {
	$feed = 'https://'.$subdomain.'.podigee.io/feed/mp3';
	$feedcontent = feed2array($feed);
	$episode_to_post = false;
	if ($feedcontent != false && count($feedcontent) > 0) foreach ($feedcontent as $episode):
		if ($episode['number'] == $episodenumber):
			$episode_to_post = $episode;
			break;
		endif;
		if (substr($episodenumber,0,1) == 'b' && $episode['episodetype'] == 'bonus' && 'b'.$episode['number'] == $episodenumber):
			$episode_to_post = $episode;
			break;
		endif;
		if (substr($episodenumber,0,1) == 't' && $episode['episodetype'] == 'teaser' && 't'.$episode['number'] == $episodenumber):
			$episode_to_post = $episode;
			break;
		endif;
	endforeach;
	if ($episode_to_post == false) return false;
	$podcast = $subdomain;
	$content = isset($episode_to_post['content']) ? $episode_to_post['content'] : "";
	$subtitle = isset($episode_to_post['subtitle']) ? $episode_to_post['subtitle'] : "";
	$episodetype = $episode_to_post['episodetype'];#
	$episodetpnumber = $episode_to_post['number'];
	$link = 'https://'.$podcast.'.podigee.io/'.($episodetype != "full" ? substr($episodetype,0,1) : '').$episodetpnumber.'-wordpress'; 
	$playershortcode = '[podigee-player url="'.$link.'"]';
	$me = wp_get_current_user();
    

    $episode_to_post['pubDate'] = strtotime($episode_to_post['pubDate']);
	
	$post = array(
          'post_title'     => ($episode_to_post['title']), 
          'post_status'    => 'draft', 
          'post_author'    => $me->ID, 
           'post_date'      => $episode_to_post['pubDate'], 
           'post_content'   => '<p><strong>'.$subtitle.'</strong></p><p>'.$playershortcode.'</p><p>'.($content)."</p>",
           //'edit_date'		=> true
        );
	if ($episode_to_post['pubDate'] != false) $post['post_date'] = date("Y-m-d H:i:s", $episode_to_post['pubDate']);

	 $post_id = wp_insert_post( $post, false );
	 if (!is_wp_error($post_id)) return $post_id; else return false;
}

/*
* Actually, this one really is a POST operation, that calls the respective GET function above multiple times.
*/
function pfex_handle_post_new_bulk($post) {
	if (!isset($post['cbepisode'])) return false;
	if (!is_array($post['cbepisode'])) return false;
	if (count($post['cbepisode']) == 0) return false;
	$return = array(); 
	foreach ($post['cbepisode'] as $episode) {
		if (substr_count($episode, '#') != 1) continue;
		$subdomain = substr($episode,0,strpos($episode, '#'));
		$episodenumber = substr($episode,strpos($episode, '#')+1);
		$postresult = pfex_handle_post_new($subdomain, $episodenumber);
		if ($postresult != false) $return[] = $postresult;
	}
	return $return;
}

function register_session(){
	global $_PFEX_DEBUG;
	if( !session_id() ) session_start();
	if (isset($_GET['pfex-debug'])) $_SESSION = $_GET['pfex-debug'];
	if (isset($_SESSION['pfex-debug']) && $_SESSION['pfex-debug'] == "1") $_PFEX_DEBUG = $_SESSION['pfex-debug'];
}
add_action('init','register_session');


/** 
 * Registering the plugin options, validation function, etc.
 */
function pfex_plugin_admin_init(){
	global $_PFEX_POST_INSERTED;
	global $_PFEX_DEBUG;
	if ($_SERVER['REQUEST_METHOD'] == "GET" && !empty($_GET['action']) && $_GET['action'] == "new" && !empty($_GET['subdomain']) && !empty($_GET['episode']) && (is_numeric($_GET['episode']) || is_numeric(substr($_GET['episode'],1)))) {
		// The GET request for creating a single new post. 
		$postreturn = pfex_handle_post_new($_GET['subdomain'], $_GET['episode']);
		if ($postreturn != false) $_PFEX_POST_INSERTED = array($postreturn); else $_PFEX_POST_INSERTED = __('Error while saving new post.', 'podigee-quick-publish');
	} elseif ($_SERVER['REQUEST_METHOD'] == "POST" && ((!empty($_POST['action']) && $_POST['action'] == "new post") || (!empty($_POST['action2']) && $_POST['action2'] == "new post")) && !empty($_POST['cbepisode'])) {
		// The POST request for creating several new posts.
		$postreturn = pfex_handle_post_new_bulk($_POST);
		if ($postreturn != false && is_array($postreturn)) $_PFEX_POST_INSERTED = $postreturn; else $_PFEX_POST_INSERTED = __('Error while saving new posts.', 'podigee-quick-publish');
	} 

	$_SESSION['pfex-debug'] = $_PFEX_DEBUG;

	if (!empty($_PFEX_POST_INSERTED) && count($_PFEX_POST_INSERTED) > 0) {
		$redirectUrl = $_SERVER['PHP_SELF'].'?page='.$_REQUEST['page'].(!empty($_GET['paged']) && is_numeric($_GET['paged']) ? "&paged=".$_GET['paged'] : "" );
		$_SESSION['pfex-new-posts-added'] = $_PFEX_POST_INSERTED;
		if ( wp_redirect( $redirectUrl ) ) {
		    exit;
		}
	}

	if (!empty($_SESSION['pfex-new-posts-added']) && count($_SESSION['pfex-new-posts-added'] ) > 0):
		$_PFEX_POST_INSERTED = $_SESSION['pfex-new-posts-added'] ;
		unset($_SESSION['pfex-new-posts-added'] );
	endif; 

	// Drawing the setup section
	register_setting( 'pfex_plugin_options', 'pfex_plugin_options', 'pfex_options_validate' );
	add_settings_section('pfex_plugin_main', '', 'pfex_plugin_section_setting_fields', 'podigee-wpqp-plugin');
	add_settings_field('pfex_slug', __('Your podcast&apos;s subdomain:','podigee-quick-publish'), 'pfex_plugin_setting_slug', 'podigee-wpqp-plugin', 'pfex_plugin_main');
	add_settings_field('pfex_api', __('Your Podigee auth token:', 'podigee-quick-publish'), 'pfex_plugin_setting_token', 'podigee-wpqp-plugin', 'pfex_plugin_main');
	add_settings_field('pfex_welcome', __('Show welcome info screen:', 'podigee-quick-publish'), 'pfex_plugin_setting_welcome', 'podigee-wpqp-plugin', 'pfex_plugin_main');
}
add_action('admin_init', 'pfex_plugin_admin_init');

/**
* The section to which the options field are attached to. Can obviously be empty though.
*/
function pfex_plugin_section_setting_fields() {

}

/* 
* This just draws the Podigee logo in the upper right corner.
*/
function pfex_plugin_section_head() 
{
	$logo_path = plugin_dir_url(__FILE__) . "res/podigee-logo.png";
	echo "<a href=\"https://www.podigee.com/de\" target=\"_blank\"><img src=\"{$logo_path}\" class=\"pfex-podigee-img-right\" /></a>";
}

/* 
* This draws the info card.
*/
function pfex_plugin_section_text() {
	$options = get_option('pfex_plugin_options');
	$authorized = check_authorization($options['pfex_slug'], $options['pfex_token']);
	if (isset($options['pfex_welcome'])) {
		$style_hide = ($options['pfex_welcome'] == true || $options['pfex_welcome'] == "true" || !$authorized ? "" : "pfex-hidden");
	} else {
		$style_hide = "";
	}
	echo '<div class="card '.$style_hide.'" id="pfex-welcome-card"><h2 class="title" style="inline">';
	_e('Woohaaa?! What is happening here?', 'podigee-quick-publish');
	echo "</h2><p>";
	_e('Hey there! We\'ve just upgraded your Podigee plugin to make Gutenberg-compatible blog posts based on your podcast content. ', 'podigee-quick-publish');
	_e('We\'ve also changed the way you import podcast data. So don\'t worry if the plugin next to the post editor looks a bit different. ', 'podigee-quick-publish');
	_e('We\'ve also moved your plugin options out of the settings menu here to make this page your one-stop Wordpress podcast shop. ', 'podigee-quick-publish');
	echo "</p><p>";
	_e('So why don\'t you just click on the link below your newest episode to instantly copy your content over to the post editor. ', 'podigee-quick-publish');
	echo "</p>";
	
	if ($authorized):
		echo '<p class="pfex-auth-success">';
		_e('<strong>Oh, and by the way</strong>: authorization <span>succeeded</span>!<br />Choose an episode to begin – or <a class="pfex-show-settings" href="javascript:void(0);">show setup section</a>.', 'podigee-quick-publish');
	else:
		echo '<p class="pfex-auth-failed">';
		_e('<strong>Oh, and by the way</strong>: authorization <span>failed</span>.<br />Please check your settings below.', 'podigee-quick-publish');
	endif;

	echo '</p>';
	echo '</div>';	
}

/*
* Draws a WP_List_Table and fills it with the feed items.
*/
function pfex_plugin_section_feeditems() {
	$options = get_option('pfex_plugin_options');
	if (!check_authorization($options['pfex_slug'], $options['pfex_token'])):
		//_e('<p>Couldn\'t fetch feed: authorization failed! Have you set up the plugin yet?</p>', 'podigee-quick-publish');
		return false;
	endif;

	echo '<form action="?page='.$_REQUEST['page'].(!empty($_GET['paged']) && is_numeric($_GET['paged']) ? "&paged".$_GET['paged'] : "").'" method="POST" id="pfex-bulk-form">';
	$podigeeTable = new My_List_Table();

	$entrys = pfex_get_database_entrys();

	if($entrys)
	{

		foreach($entrys as $entry)
		{
	
			$foundposts = (query_posts(array(
				'post_status' => array('publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit'),  
	    		's' => $entry['title'],
	    		'orderby' => 'date', 
   				'order'   => 'DESC',
   				'posts_per_page' => 1
	    	))); 

	    	if($foundposts && count($foundposts) > 0) 
			{
	    		$foundid = $foundposts[0]->ID; 
				$entry['editlink'] = "post.php?post={$foundid}&action=edit";
				$entry['previewlink'] = "?p={$foundid}&preview=true";
				$entry['title'] = "<a href=\"{$entry['editlink']}\">{$entry['title']}</a>";
	    	} 
			
			

	    	$podigeeTable->addData($entry);
		}
	}

	echo '<div class="wrap"><h3>';
	_e('These are the episodes in your connected feeds:', 'podigee-quick-publish');
	echo '</h3>'; 
	$podigeeTable->prepare_items(); 
	$podigeeTable->display(); 
	echo '</div></form>'; 

}

/**
* Options and explanation for the settings page 
*/
function pfex_plugin_setting_slug() {
	$options = get_option('pfex_plugin_options');
	echo "<input id='pfex_slug' name='pfex_plugin_options[pfex_slug]' size='40' type='text' value='{$options['pfex_slug']}' />";
	_e("<p>Please do not enter the full podcast URL here – only the subdomain as configured in the <i>General</i> section of your podcast&apos;s settings.<br /><i>Example</i>: If your Podcast is located at <strong>https://mypreciouspodcast.podigee.io</strong> – you would only need to enter <strong>mypreciouspodcast</strong>.</p>", 'podigee-quick-publish');
	if (isset($options['pfex_slug']) && trim($options['pfex_slug']) != "") {
		$subdomains = explode(",", $options['pfex_slug']);
		$auth = check_authorization($options['pfex_slug'], $options['pfex_token']);
		if (count($subdomains) > 0 && $auth) {
			echo "<br /><p>";
			_e('If configured correctly, you should be able to reach your feed(s) at:', 'podigee-quick-publish');
			echo "<br /><ul>";
			foreach ($subdomains as $subdomain) {
				$mp3feed = "https://".$subdomain.".podigee.io/feed/mp3/";
				echo "<li><a href=\"$mp3feed\" target=\"_blank\">$mp3feed</a>".
					(pfex_check_url($mp3feed) ? " <div style=\"display: inline\" class=\"pfex-auth-success\"><span>[OK]</span></div>" : " <div style=\"display: inline\" class=\"pfex-auth-failed\"><span>[X]</span></div>").
					"</li>";
			}
			echo "</ul></p>";
		}
	}
	_e("<p>Did you know? You can add multiple subdomains in a comma-separated list.</p>", 'podigee-quick-publish');
}

/**
* Checking feed availability
*/
function pfex_check_url($url) {
	global $_PFEX_DEBUG;
	if (function_exists('curl_init')):
		$handle = curl_init($url);
		curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);
		$response = curl_exec($handle);
		$httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
		curl_close($handle);
		if($httpCode == 200){
			if ($_PFEX_DEBUG) pfex_log(true, "URL check with curl was successful", array("url" => $url));
			return true;
		} else {
			 if ($_PFEX_DEBUG) pfex_log(false, "URL check with curl was not successful.", array("url" => $url, "httpCode" => $httpCode));
			 else return false;
		}
	else:
		if ($_PFEX_DEBUG) pfex_log(false, "Curl is not installed for URL check.");
	endif;
	try {
		$devnull = file_get_contents($url);
		if (!($devnull)) {
			if ($_PFEX_DEBUG) pfex_log(false, "URL check with file_get_contents failed.", array("url" => $url, "devnull" => $devnull));
			return false; 
		} else {
			if ($_PFEX_DEBUG) pfex_log(false, "URL check with file_get_contents successful.", array("url" => $url));
			return true;
		}
	} catch (Exception $e) {
		if ($_PFEX_DEBUG) pfex_log(false, "An exception occurred while URL checking with file_get_contents.", array("url" => $url, "exception" => $e));
		return false;
	}
	if ($_PFEX_DEBUG) pfex_log(false, "Something went wrong while URL checking.", array("url" => $url));
	return false;
}

/**
* Options and explanation for the settings page 
*/
function pfex_plugin_setting_token() {
	$options = get_option('pfex_plugin_options');
	echo "<input id='pfex_token' name='pfex_plugin_options[pfex_token]' size='40' type='text' value='{$options['pfex_token']}' /><br />";
	_e("Please enter the auth token as displayed <a href=\"https://app.podigee.com/settings#applications\" target=\"_blank\">here</a>.", 'podigee-quick-publish');
}

/**
* Options and explanation for the settings page 
*/
function pfex_plugin_setting_welcome() {
	$options = get_option('pfex_plugin_options');
	echo "<input type='checkbox' id='pfex_welcome' name='pfex_plugin_options[pfex_welcome]' value='1' ".( $options['pfex_welcome'] == true ? "checked":"")." /><br />";
}

/**
* Validation for the plugin settings  
*/
function pfex_options_validate($input) {
	$options = get_option('pfex_plugin_options');
	$options['pfex_token'] = strtolower(trim($input['pfex_token']));
	if(!preg_match('/^[a-z0-9]{32}$/i', $options['pfex_token'])) {
		$options['pfex_token'] = '';
	}
	$options['pfex_slug'] = strtolower(trim(str_replace(" ", "", $input['pfex_slug'])));
	if(!preg_match('/^[a-z0-9-_,]+$/i', $options['pfex_slug'])) {
		$options['pfex_slug'] = '';
	}
	$options['pfex_welcome'] = ( isset($input['pfex_welcome']) && $input['pfex_welcome'] == true ? true : false);
	global $_PFEX_DEBUG;
	if ($_PFEX_DEBUG) pfex_log(true, "Options saved.", $options);
	return $options;
}

/**
* Fetches podcast feed.
*/
 function feed2array($url) {
	global $_PFEX_DEBUG;
	if (strpos($url, ".podigee.io") == false) $this->is_podigee_feed = false;
	if (class_exists("DOMdocument")) {
		if ($_PFEX_DEBUG) pfex_log(true, "DOMdocument exists –> using it.");
		$rss = new DOMDocument();
	    @$rss->loadXML(pfex_url_get_contents($url));
	    $feed = array();
	    //echo $url."<br />";
	    foreach ($rss->getElementsByTagName('item') as $node) {
	    	//echo "  ".trim(@$node->getElementsByTagName('title')->item(0)->nodeValue)."<br />";
	        if (count($node->getElementsByTagName('enclosure')) > 0):
		        $episode = array (
		                'title' => trim(@$node->getElementsByTagName('title')->item(0)->nodeValue),
		                'link' => trim(@$node->getElementsByTagName('link')->item(0)->nodeValue),
		                'pubDate' => trim(@$node->getElementsByTagName('pubDate')->item(0)->nodeValue),
		                'description' => trim(@$node->getElementsByTagName('description')->item(0)->nodeValue),
		                'content' => trim(@$node->getElementsByTagName('encoded')->item(0)->nodeValue),
		                'media' => trim(@$node->getElementsByTagName('enclosure')->item(0)->getAttribute('url')),
		                'number' => trim(@$node->getElementsByTagName('episode')->item(0)->nodeValue),
		                'episodetype' => trim(@$node->getElementsByTagName('episodeType')->item(0)->nodeValue),
		                'season' => trim(@$node->getElementsByTagName('season')->item(0)->nodeValue)
		                );
				array_push($feed, $episode);
			else:
				if ($_PFEX_DEBUG) pfex_log(false, "Feed node has no enclosure.", array("title" => trim(@$node->getElementsByTagName('title')->item(0)->nodeValue), "link" =>trim(@$node->getElementsByTagName('link')->item(0)->nodeValue)));
			endif;
	    }
	    if ($_PFEX_DEBUG) pfex_log(true, "DOMdocument worked and retrieved ".count($feed)." feed entries.", array("url" => $url));
	} else {
		try {
			if ($_PFEX_DEBUG) pfex_log(false, "DOMdocument does not exist – trying SimpleXML instead.");
			$rss = file_get_contents($url);
			$xml = simplexml_load_string($rss, 'SimpleXMLElement', LIBXML_NOCDATA);
			$feed = array();
			foreach($xml->channel->item as $item){
				$itunes = ($item->children("itunes", true));
				$episode = array (
					'title' => trim(@$item->title),
					'link' => trim(@$item->link),
					'pubDate' => trim(@$item->pubDate),
					'description' => trim(@$item->description),
					'content' => trim(@$item->children("content", true)),
					'media' => trim(@$item->enclosure['url']),
					'number' => trim(@$itunes->episode),
		            'episodetype' => trim(@$itunes->episodeType),
		            'season' => trim(@$itunes->season)
				);
				array_push($feed, $episode);
			}
			if ($_PFEX_DEBUG) pfex_log(true, "SimpleXML worked and retrieved ".count($feed)." feed entries.", array("url" => $url));
		} catch (Exception $e) {
			if ($_PFEX_DEBUG) pfex_log(false, "SimpleXML threw an error.", array("error" => $e));
			wp_die('error');
		}
	}
    return $feed;
}

/**
* Checks if the auth token is valid.
*/
function check_authorization($subdomain, $token) {
	global $_PFEX_LOGIN_OKAY;
	global $_PFEX_DEBUG;
	if ($_PFEX_LOGIN_OKAY) return true;
	if (!isset($subdomain) || !isset($token) || $subdomain == false || $token == false): 
		$_PFEX_LOGIN_OKAY = false;
		if ($_PFEX_DEBUG) pfex_log(false, "No subdomain or no token set.");
		return false;
	endif;
	if (!is_array($subdomain)):
		if (is_string($subdomain)):
			if (substr_count($subdomain, ",") == 0): 
				$subdomain = array($subdomain); 
			else:
				$subdomain = explode(",", $subdomain);
			endif;
		else: 
			$_PFEX_LOGIN_OKAY = false;
			if ($_PFEX_DEBUG) pfex_log(false, "Subdomain not an array but also not a string.", $subdomain);
			return false;
		endif;
	endif;
	if (count($subdomain) == 0): 
		$_PFEX_LOGIN_OKAY = false;
		if ($_PFEX_DEBUG) pfex_log(false, "Subdomain-Array has length 0.");
		return false;
	endif;
	$authorization = false;
	foreach ($subdomain as $slug):
		$url = "https://app.podigee.io/apps/wordpress-quick-publish/authorize";
		$data = array("subdomain" => $subdomain);                                                                    
		$data_string = json_encode($data);     

		$data = wp_remote_post($url, array(
		    'headers'     => array('Content-Type' => 'application/json', 'Token' => $token),
		    'body'        => $data_string,
		    'method'      => 'POST',
		    'data_format' => 'body',
		    'sslverify'	  => false,
		));

		if ( is_wp_error( $data ) ) {
		    $error_string = $data->get_error_message();
		    if ($_PFEX_DEBUG) pfex_log(false, $error_string);
		    die($error_string);
		} else if (is_array($data) && isset($data['response']['code']) && $data['response']['code'] == 200):
			$_PFEX_LOGIN_OKAY = true;
			if ($_PFEX_DEBUG) pfex_log(true, "Authorization was successful.", $subdomain, array("token" => $token));
			return true; 
		endif;
	endforeach;
	$_PFEX_LOGIN_OKAY = false;
	if ($_PFEX_DEBUG) pfex_log(false, "Authorization failed: out of options.", $subdomain, array("token" => $token));
	return false;
} 

/**
* Custom logging function.
*/
function pfex_log($allgood, $str, $data = false, $data2 = false) {
	$logfile = dirname(__FILE__)."/log.txt";
	touch($logfile);
	$str = ($allgood ? "[OK]\t" : "[ERR]\t").date("Y-m-d H:i:s")."\t".$str."\n";
	if ($data) {
		if (is_string($data)) $str .= "  |-> ".$data."\n";
		if (is_array($data)) foreach($data as $key => $value) $str .= "  |-> ".$key.":\t".$value."\n";
	}
	if ($data2) {
		if (is_string($data2)) $str .= "  |-> ".$data."\n";
		if (is_array($data2)) foreach($data2 as $key => $value) $str .= "  |-> ".$key.":\t".$value."\n";
	}
	file_put_contents($logfile, $str,FILE_APPEND);
}

/*
* Had to add this download function to fix the few cases in which the XML would return empty.
*/
function pfex_url_get_contents ($Url) {
    if (!function_exists('curl_init')){ 
    	pfex_log(false, "CURL is not installed – try using file_get_contents instead");
        return file_get_contents(($url));
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $Url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}

/*
* This is the class for our custom table that displays the feed items.
*/

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class My_List_Table extends WP_List_Table {

	public function addData($array) {
		if (is_array($array) == false) return false;
		if (count($array) == 0) return false;
		$this->items[] = $array;
	}

	public function setData($array) {
		if (is_array($array) == false) return false;
		if (count($array) == 0) return false;
		$this->items = array();
		foreach ($array as $dataset):
			if (is_array($dataset) == false) continue;
			if (count($dataset) == 0) continue;
			$row = array();
			foreach ($dataset as $key => $value) {
				$row[$key] = $value;
			}
			$this->items[] = $row;
		endforeach;
	}

	function get_columns(){
	  $columns = array(
	  	 'cb'        => '<input type="checkbox" />',
	     'pubdate'	=> __('Published', 'podigee-quick-publish'),
	     'title'		=> __('Episode title', 'podigee-quick-publish') ,
	     'podcast' 	=> __('Podcast', 'podigee-quick-publish'),
	     'episodetype' => __('Type', 'podigee-quick-publish'),
	     'episodenumber' => __('E#', 'podigee-quick-publish'),
	     'shortcode'	=> __('Shortcode', 'podigee-quick-publish')
	      
	  );
	  return $columns;
	}

	function prepare_items() {
	  $columns = $this->get_columns();
	  $hidden = array();
	  $sortable = $this->get_sortable_columns();
	  $this->_column_headers = array($columns, $hidden, $sortable);
	  usort( $this->items, array( &$this, 'usort_reorder' ) );

	  $per_page = 15;
	  $current_page = $this->get_pagenum();
	  $total_items = count($this->items);

	  $found_data = array_slice($this->items,(($current_page-1)*$per_page),$per_page);
	  
	  $this->set_pagination_args( array(
	    'total_items' => $total_items,                  
	    'per_page'    => $per_page                     
	  ) );
	  $this->items = $found_data;
	}

	function column_default( $item, $column_name ) {
	  switch( $column_name ) { 
	    case 'podcast':
	    case 'pubdate':
	    case 'episodenumber':
	    case 'shortcode':
	    case 'title':
	    case 'episodetype':
	      return $item[ $column_name ];
	    default:
	      return print_r( $item, true ) ; 
	  }
	}

	function get_sortable_columns() {
	  $sortable_columns = array(
	  	'pubdate' => array('pubdate', false), 
	  	'title' => array('title', false),
	  	'podcast' => array('podcast', false), 
	  	'episodenumber' => array('episodenumber', false)
	  	);
	  return $sortable_columns;
	}

	function usort_reorder( $a, $b ) {
		$orderby = ( ! empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'pubdate';
		if (empty($_GET['orderby'])) $_GET['order'] = 'desc';
		$order = ( ! empty($_GET['order'] ) ) ? $_GET['order'] : 'asc';
		$result = strnatcmp( $a[$orderby], $b[$orderby] );
		return ( $order === 'asc' ) ? $result : -$result;
	}

	function column_title($item) {
		$pagination = "";
		if (!empty($_GET['paged']) && is_numeric($_GET['paged'])) $pagination = "&paged=".$_GET['paged'];
	  $actions = array(
	            'new post'      => sprintf('<a href="%s?page=%s&action=%s&subdomain=%s&episode=%s%s">%s</a>',$_SERVER['PHP_SELF'], $_REQUEST['page'],'new',$item['podcast'], $item['episodenumber'], $pagination, __('&gt;&gt; turn into post', 'podigee-quick-publish'))
	        );

	  return sprintf('%1$s %2$s', $item['title'], $this->row_actions($actions) );
	}

	function column_shortcode($item) {
	  $actions = array(
	            'copy'      => sprintf('<a href="javascript:void(0);" class="pfex-copy-shortcode">%s</a>',__('&gt;&gt; copy', 'podigee-quick-publish'))
	        );

	  return sprintf('%1$s %2$s', $item['shortcode'], $this->row_actions($actions) );
	}

	function get_bulk_actions() {
	  $actions = array(
	    'new post'    => __('New posts from episodes', 'podigee-quick-publish')
	  );
	  return $actions;
	}

	function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="cbepisode[]" value="%s#%s" />', $item['podcast'], $item['episodenumber']
        );    
    }

}

