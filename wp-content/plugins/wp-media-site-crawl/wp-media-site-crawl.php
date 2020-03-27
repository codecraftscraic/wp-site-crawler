<?php
defined( 'ABSPATH' ) or die( 'No scripts!' );
/**
 * Plugin Name: WP Media Site Crawl
 * Plugin URI: http://www.github.com/codecraftscraic/wp-media-site-crawl
 * Description: WP site crawl. Creates a sitemap for SEO analysis
 * Version: 1.0
 * Author: Jenny Rasmussen
 * Author URI: http://www.nextjenmobile.com
 */

//Activation tasks: create database for storage
register_activation_hook( __FILE__, 'wpmedia_install' );

function wpmedia_install() {
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

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}

//create the plugin options menu item
add_action( 'admin_menu', 'wpmedia_site_crawl_menu' );

function wpmedia_site_crawl_menu() {
    add_options_page( 'Site Crawl Settings', 'Site Crawl', 'manage_options',
        'wpmedia-site-crawl-settings', 'wpmedia_site_crawl_options');
}

//create options page
function wpmedia_site_crawl_options() {
    if ( !current_user_can( 'manage_options' ) ) {
        wp_die( __( 'You do not have permission to access these settings.' ) );
    }

    echo '<style>';
	echo '	ul {';
	echo '		margin-left: 3%;';
	echo '	}';

	echo '	h3 {';
	echo '		margin-top: 2%;';
	echo '	}';
	echo '';

	echo '</style>';

    echo '<h1>Site Crawl Settings</h1>';
    echo '<h2>Start a site crawl, view a sitemap, and show your sitemap to users</h2>';

    echo '<div>';

    echo '	<p>';
    echo '		Clicking on Start Site Crawl will start a crawl of your site, and set a job to re-crawl your site every
          		hour. This will create a sitemap that is visible to you and your users. At the start of each crawl,
          		the old data will be removed and an entirely new set of data will be stored.';
	echo '	</p>';

	//TODO remove this once sitemap.html is working
//	echo '	<p>';
//	echo '		To display this sitemap to your users use the shortcode [ wpmedia_sitemap ] (no spaces, including brackets)
//				where desired.';
//	echo '	</p>';

	echo '<form method="POST">';
    echo '<button type="submit" name="site-crawl">Start Site Crawl</button>';
	echo '</form>';


    if( array_key_exists('site-crawl', $_POST) ) {
    	manual_trigger_site_crawl();
    	sitemap();
	}

    //display sitemap
	echo '<div>' . sitemap() . '</div>';

	//disable button on click, and if cron set, on page load
    echo '<script>';
	echo 'jQuery(document).ready(function( $ ) {';
	echo '	$("button").click(function(){';
	echo '		$("button").attr("disabled", "true");';
	echo '	});';

	if( wp_next_scheduled( 'crawl_cron' ) ) {
		echo '	$("button").attr("disabled", "true")';
	}

	echo '});';
	echo '</script>';
}

function manual_trigger_site_crawl() {
    set_cron();
    crawl_site();
}

function delete_old_data() {
    global $wpdb;
    $table = $wpdb->prefix . 'wpmedia_site_crawl';

    $target_time = strtotime('-1 hour');

    $sql = 'DELETE FROM ' . $table . ' WHERE `timestamp` > ' . $target_time;

    $wpdb->query($sql);
}

function set_cron() {
    $schedules['hourly'] = array(
        'interval' => 3600,
        'display'  => esc_html__( 'Every Hour' ),
    );

    return $schedules;
}

add_action('crawl_cron', 'crawl_site');

if ( ! wp_next_scheduled( 'crawl_cron' ) ) {
    wp_schedule_event( time(), 'hourly', 'crawl_cron' );
}

function crawl_site() {
    delete_old_data();

    global $wpdb;
    $table = $wpdb->prefix . 'wpmedia_site_crawl';

    //get the website homepage
    $site_html = file_get_contents(get_site_url());

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

        //make sure there is a link and that it's not an anchor link
        if( '' === strlen(trim($link_value)) ) {
            continue;
        }

        if( '#' === $link_value[0] ) {
            continue;
        }

		if( !parse_url($link_value, PHP_URL_PATH) ) {
			continue;
		}

        $sql = 'INSERT INTO ' .$table .' (`link_text`, `link`)
            VALUES ("'. $link_text . '", "' . $link_value . '");';

        if ( $sql ) {
        	$wpdb->query($sql);
            continue;
        } else {
            //TODO ERROR REPORTING
            break;
        }
    }
}

//create sitemap and shortcode
function sitemap() {
    global $wpdb;
    $table = $wpdb->prefix . 'wpmedia_site_crawl';

    $sql = 'SELECT * FROM ' . $table;

    $stored_links = $wpdb->get_results($sql);

    if ($stored_links) {

    	$site_url = get_site_url();
    	$blog_name = get_bloginfo('name');

    	$sitemap_html = '<h3><a href="' . $site_url . '">' . $blog_name . '</a></h3>
		<ul>';

		foreach ($stored_links as $stored_link) {
			$sitemap_html .= '<li><a href="'. $stored_link->link .'">' . $stored_link->link_text . '</a></li>';
		}

		$sitemap_html .= '</ul>';
    }

    file_put_contents('sitemap.html', $sitemap_html);
    return $sitemap_html;
}

//TODO remove this once sitemap.html is working
//add_shortcode('wpmedia_sitemap', 'sitemap');


//Deactivation tasks: delete custom table, remove cron

function wpmedia_deactivate() {
    global $wpdb;
    $table = $wpdb->prefix . 'wpmedia_site_crawl';

    $sql = "DROP TABLE IF EXISTS $table;";

    $wpdb->query($sql);

	$timestamp = wp_next_scheduled( 'crawl_cron' );
	wp_unschedule_event( $timestamp, 'crawl_cron' );

    delete_option('my_plugin_db_version');

}

register_deactivation_hook( __FILE__, 'wpmedia_deactivate' );
