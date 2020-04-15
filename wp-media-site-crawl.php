<?php
// @CODEREVIEW: use || instead.
defined( 'ABSPATH' ) or die( 'No scripts!' );

/**
 * @CODEREVIEW:
 *
 * 1. require and require_once are not functions.
 * 2. Use WP_Filesystem_Direct. See our implementation here https://github.com/wp-media/wp-rocket/blob/master/inc/functions/files.php#L1110-L1113
 * 3. Load these files when needed.
 */
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/upgrade.php';

/**
 * Plugin Name: WP Media Site Crawl
 * Plugin URI: http://www.github.com/codecraftscraic/wp-media-site-crawl
 * Description: WP site crawl. Creates a sitemap for SEO analysis
 * Version: 1.0
 * Author: Jenny Rasmussen
 * Author URI: http://www.nextjenmobile.com
 */

/**
 * @CODEREVIEW: Use namespace. Why?
 *
 * 1. Best practice for modern PHP development.
 * 2. Reduces the risk of name conflicts.
 */

class WpSiteCrawl {
	function __construct() {
		/**
		 * @CODEREVIEW: Hook event registrations should be in a public method instead of in the constructor.
		 * Why? Testing.
		 */
		//activation
		register_activation_hook( __FILE__, array($this, 'wpmedia_activate' ) );

		//create the plugin options menu item
		add_action( 'admin_menu', array($this, 'wpmedia_site_crawl_menu') );

		add_action('sitemap', array($this, 'sitemap') );

		//cron hook
		add_action('crawl_cron', array($this, 'crawl_site') );

		//deactivation
		register_deactivation_hook( __FILE__, array($this, 'wpmedia_deactivate') );
	}

	/**
	 * @CODEREVIEW: methods do not need to be prefixed. Instead, name each to tell us what the expected behavior will be.
	 */
	function wpmedia_activate() {
		global $wpdb;
		$table = $wpdb->prefix . 'wpmedia_site_crawl';

		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table (
        id int(10) NOT NULL AUTO_INCREMENT,
        link_text varchar(255),
        link varchar(255),
        timestamp datetime DEFAULT NOW() NOT NULL,
        PRIMARY KEY id (id)
    ) $charset;";

		dbDelta( $sql );
	}

	function wpmedia_site_crawl_menu() {
		// @CODEREVIEW: String literals should be internationalized. How? Use __( 'Site literal', 'plugin-text-domain' );
		add_options_page( 'Site Crawl Settings', 'Site Crawl', 'manage_options',
			'wpmedia-site-crawl-settings', array($this, 'wpmedia_site_crawl_options'));
	}

	//create options page
	function wpmedia_site_crawl_options() {
		if ( !current_user_can( 'manage_options' ) ) {
			// @CODEREVIEW: Add your plugin's text domain as the 2nd arg passed into __().
			wp_die( __( 'You do not have permission to access these settings.' ) );
		}


		$sitemap_url = get_site_url() . '/wp-content/plugins/wp-media-site-crawl/sitemap.html';

		// @CODEREVIEW: String literals should be internationalized and
		/**
		 * @CODEREVIEW:
		 *
		 * 1.   Security Issue: variables must be escaped before rendering out to the browser.
		 *      For the `$sitemap_url`, use `esc_url()`. @link https://developer.wordpress.org/reference/functions/esc_url/
		 * 2.   Security Issue: no nonce field added to the form.
		 *      @link https://developer.wordpress.org/reference/functions/wp_nonce_field/
		 * 3.   Separation of concerns: The HTML should be contained in a view file.
		 *      See the example I provided in the views/ folder.
		 * 4.   String literals should be internationalized and escaped with `esc_html_e()`. @link https://developer.wordpress.org/reference/functions/esc_html_e/
		 * 5.   Instead of echoing, leverage raw HTML. Why? Easier to read, understand, and maintain.
		 */
		echo '<h1>Site Crawl Settings</h1>';
		echo '<h2>Start a site crawl, view a sitemap, and show your sitemap to users</h2>';

		echo '<div>';

		echo '	<p>';
		echo '		Clicking on Start Site Crawl will start a crawl of your site, and set a job to re-crawl your site every
          		hour. This will create a sitemap that is visible to you and your users. At the start of each crawl,
          		the old data will be removed and an entirely new set of data will be stored.';
		echo '	</p>';

		echo '	<p>';
		echo '		After your initial crawl, a user visible sitemap can be found at <a target="_blank" href="' . $sitemap_url . '">'
					. $sitemap_url . '</a>';
		echo '	</p>';

		echo '<form method="POST">';
		echo '<button type="submit" name="site-crawl">Start Site Crawl</button>';
		echo '</form>';


		if( array_key_exists('site-crawl', $_POST) && ! wp_next_scheduled( 'crawl_cron' )) {
			$this->manual_trigger_site_crawl();
			$this->sitemap();
		}

		//display sitemap
		echo '<div>' . $this->sitemap() . '</div>';

		//disable button if cron set
		echo '<script>';
		echo 'jQuery(document).ready(function( $ ) {';

		if( wp_next_scheduled( 'crawl_cron' ) ) {
			echo '	$("button").attr("disabled", "true")';
		}

		echo '});';
		echo '</script>';
	}

	function manual_trigger_site_crawl() {
		$this->crawl_site();

		if ( ! wp_next_scheduled( 'crawl_cron' ) ) {
			wp_schedule_event( time(), 'hourly', 'crawl_cron' );
		}
	}

	function delete_old_data() {
		global $wpdb;
		$table = $wpdb->prefix . 'wpmedia_site_crawl';

		$target_time = strtotime('-1 hour');

		/**
		 * @CODEREVIEW: Security Issue.
		 *
		 * The variables are not sanitized before storing in the database. Use $wpdb->prepare().
		 * @link https://developer.wordpress.org/reference/classes/wpdb/prepare/
		 */
		$sql = 'DELETE FROM ' . $table . ' WHERE `timestamp` > ' . $target_time;

		$wpdb->query($sql);
	}

	function crawl_site() {
		/**
		 * @CODEREVIEW: Decoupling.
		 *
		 * Instead of using globals, inject both into the class and store as properties.
		 */
		global $wp_filesystem;
		global $wpdb;

		WP_Filesystem();

		$this->delete_old_data();

		$table = $wpdb->prefix . 'wpmedia_site_crawl';

		//get the website homepage
		/**
		 * @CODEREVIEW:
		 *
		 * 1. Be consistent. Use the filesystem's get_contents() here.
		 * 2. Another approach is to use wp_remote_get().
		 */
		$site_html = file_get_contents(get_site_url()); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

		/**
		 * @CODEREVIEW:
		 *
		 * Why search for the index.html file? Your implementation is storing it in this plugin's root directory.
		 * This plugin's file is also in the same directory.
		 *
		 *  Instead, simplify.
		 */
		//save site homepage as html
//		$directory = $wp_filesystem->find_folder(WP_PLUGIN_DIR . "/wp-media-site-crawl");
//
//		$file = trailingslashit($directory) . "index.html";
		$file = __DIR__ . '/index.html';

		//delete old cached file
		/**
		 * @CODEREVIEW: Be consistent. Use the filesystem to delete the file.
		 */
		wp_delete_file ($file);

		$wp_filesystem->put_contents( $file, $site_html, 0644);

		//create new DOM to read homepage contents
		$site_dom = new DOMDocument;

		/*
		 * Not particularly happy to have to suppress the errors instead of fixing them, but based on what I've read
		 * there isn't really a better way with DOMDocument at this time.
		 */
		libxml_use_internal_errors(true);
		$site_dom->loadHTML($site_html);

		//get all a tags
		$site_a_tags = $site_dom->getElementsByTagName('a');

		//loop through a tags for actual links
		foreach ( $site_a_tags as $site_a_tag ) {
			$link_text = $site_a_tag->nodeValue;
			$link_value = $site_a_tag->getAttribute('href');

			//make sure there is a link, not an anchor link, and not the homepage base url
			if( '' === strlen(trim($link_value)) ) {
				continue;
			}

			if( '#' === $link_value[0] ) {
				continue;
			}

			if( !parse_url($link_value, PHP_URL_PATH) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url
				continue;
			}

			/**
			 * @CODEREVIEW: Security Issue.
			 *
			 * The values/variables are not sanitized before storing in the database. Use $wpdb->prepare().
			 * @link https://developer.wordpress.org/reference/classes/wpdb/prepare/
			 */
			$sql = 'INSERT INTO ' . $table .' (`link_text`, `link`)
            VALUES ("'. $link_text . '", "' . $link_value . '");';

			if ( $sql ) {
				$wpdb->query($sql);
				continue;
			} else {
				// @CODEREVIEW: String literal should be internationalized.
				return 'There has been an error, please try again later.';
			}
		}
	}

	//create sitemap
	function sitemap() {
		/**
		 * @CODEREVIEW: Decoupling.
		 *
		 * Instead of using globals, inject both into the class and store as properties.
		 */
		global $wpdb;
		global $wp_filesystem;
		WP_Filesystem();

		$sitemap_html = '<style> ul {margin-left: 3%; list-style-type: none;} h3 {margin-top: 2%;}
						a{color:#0073aa; font-family: sans-serif;} </style>';

		$table = $wpdb->prefix . 'wpmedia_site_crawl';

		$sql = 'SELECT * FROM ' . $table;

		$stored_links = $wpdb->get_results($sql);

		if ($stored_links) {
			$site_url = get_site_url();
			$blog_name = get_bloginfo('name');

			$sitemap_html .= '<h3><a href="' . $site_url . '">' . $blog_name . '</a></h3><ul>';

			foreach ($stored_links as $stored_link) {
				$sitemap_html .= '<li><a href="'. $stored_link->link .'">' . $stored_link->link_text . '</a></li>';
			}

			$sitemap_html .= '</ul>';
		}

		/**
		 * @CODEREVIEW:
		 *
		 * Why search for the sitemap file? Your implementation is storing it in this plugin's root directory.
		 * This plugin's file is also in the same directory.
		 *
		 *  Instead, simplify.
		 */
//		$directory = $wp_filesystem->find_folder(WP_PLUGIN_DIR . "/wp-media-site-crawl");
//
//		$file = trailingslashit($directory) . "sitemap.html";
		$file = __DIR__ . '/sitemap.html';

		/**
		 * @CODEREVIEW: You can also use the delete method that's built into $wp_filesystem to be consistent
		 * with the implementation. How?
		 *
		 * $wp_filesystem->delete( $file );
		 */
		//delete old sitemap
		wp_delete_file ($file);

		$wp_filesystem->put_contents( $file, $sitemap_html, 0644);

		return $sitemap_html;
	}

	//Deactivation tasks: delete custom table, remove cron
	function wpmedia_deactivate() {
		global $wpdb;
		$table = $wpdb->prefix . 'wpmedia_site_crawl';

		$sql = "DROP TABLE IF EXISTS $table;";

		$wpdb->query($sql);

		/**
		 * @CODEREVIEW: Good job cleaning up the CRON jobs and option.
		 */
		$timestamp = wp_next_scheduled( 'crawl_cron' );
		wp_unschedule_event( $timestamp, 'crawl_cron' );

		delete_option('wpmedia-site-crawl-settings');

	}
}

/**
 * @CODEREVIEW: Instantiating an object in the same file with the class is not a best practice.
 *
 * Why? The code is not testable.
 */
new WpSiteCrawl();
