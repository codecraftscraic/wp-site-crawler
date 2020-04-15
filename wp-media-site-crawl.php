<?php
defined( 'ABSPATH' ) or die( 'No scripts!' );
require_once( ABSPATH . 'wp-admin/includes/file.php' );
require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

/**
 * Plugin Name: WP Media Site Crawl
 * Plugin URI: http://www.github.com/codecraftscraic/wp-media-site-crawl
 * Description: WP site crawl. Creates a sitemap for SEO analysis
 * Version: 1.0
 * Author: Jenny Rasmussen
 * Author URI: http://www.nextjenmobile.com
 */

class WpSiteCrawl {
	function __construct() {
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
		add_options_page( 'Site Crawl Settings', 'Site Crawl', 'manage_options',
			'wpmedia-site-crawl-settings', array($this, 'wpmedia_site_crawl_options'));
	}

	//create options page
	function wpmedia_site_crawl_options() {
		if ( !current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have permission to access these settings.' ) );
		}

		$sitemap_url = get_site_url() . '/wp-content/plugins/wp-media-site-crawl/sitemap.html';

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

		$sql = 'DELETE FROM ' . $table . ' WHERE `timestamp` > ' . $target_time;

		$wpdb->query($sql);
	}

	function crawl_site() {
		global $wp_filesystem;
		global $wpdb;

		WP_Filesystem();

		$this->delete_old_data();

		$table = $wpdb->prefix . 'wpmedia_site_crawl';

		//get the website homepage
		$site_html = file_get_contents(get_site_url());

		//save site homepage as html
		$directory = $wp_filesystem->find_folder(WP_PLUGIN_DIR . "/wp-media-site-crawl");

		$file = trailingslashit($directory) . "index.html";

		//delete old cached file
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

			if( !parse_url($link_value, PHP_URL_PATH) ) {
				continue;
			}

			$sql = 'INSERT INTO ' . $table .' (`link_text`, `link`)
            VALUES ("'. $link_text . '", "' . $link_value . '");';

			if ( $sql ) {
				$wpdb->query($sql);
				continue;
			} else {
				return 'There has been an error, please try again later.';
			}
		}
	}

	//create sitemap
	function sitemap() {
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

		$directory = $wp_filesystem->find_folder(WP_PLUGIN_DIR . "/wp-media-site-crawl");

		$file = trailingslashit($directory) . "sitemap.html";

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

		$timestamp = wp_next_scheduled( 'crawl_cron' );
		wp_unschedule_event( $timestamp, 'crawl_cron' );

		delete_option('wpmedia-site-crawl-settings');

	}
}

new WpSiteCrawl();
