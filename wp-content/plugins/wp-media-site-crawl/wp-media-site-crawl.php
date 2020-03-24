<?php
defined( 'ABSPATH' ) or die( 'No scripts' );
/**
 * Plugin Name: WP Media Site Crawl
 * Plugin URI: http://www.github.com/codecraftscraic/wp-media-site-crawl
 * Description: WP site crawl. Creates a sitemap for SEO analysis
 * Version: 1.0
 * Author: Jenny Rasmussen
 * Author URI: http://www.nextjenmobile.com
 */

//create database for crawl storage upon plugin activation
register_activation_hook( __FILE__, 'wpmedia_install' );

function wpmedia_install() {

    global $wpdb;

    $charset = $wpdb->get_charset_collate();

    $table = $wpdb->prefix . 'wpmedia_site_crawl';

    $sql = "CREATE TABLE $table (
        id int(10) NOT NULL AUTO_INCREMENT,
        link_text varchar(255),
        link varchar(255),
        timestamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
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

//options page
function wpmedia_site_crawl_options() {
    if ( !current_user_can( 'manage_options' ) ) {
        wp_die( __( 'You do not have permission to access these settings.' ) );
    }

    echo '<h1>Site Crawl Settings</h1>';
    echo '<h2>Start a site crawl, view a sitemap, and show your sitemap to users</h2>';

    echo '<div>';
    echo '<p>Clicking on Start Site Crawl will start a crawl of your site, and set a job to re-crawl your site every hour. This will create a sitemap that is 
          visible to you and your users. At the start of each crawl, the old data will be removed and an entirely new set 
          of data will be stored.</p>';
    echo '';

    echo '<button>Start Site Crawl</button>';

    //TODO display sitemap in admin
}

function manual_trigger_site_crawl() {
    crawl_site();
    set_cron();
}

function delete_old_data() {
    //TODO delete data from database
}

function set_cron() {
    //TODO set cron job to run hourly
    crawl_site();
    sitemap();
}

function crawl_site() {
    //get the website homepage
    $site_html = file_get_contents(get_site_url());

    //create new DOM to read homepage contents
    $site_dom = new DOMDocument;
    $site_dom->loadHTML($site_html);

    //get all a tags
    $site_a_tags = $site_dom->getElementsByTagName('a');

    //create array to store links from a tags in
    $site_links = array();

    //loop through a tags for actual links
    foreach ( $site_a_tags as $site_a_tag ) {

        $link_text = $site_a_tag->nodeValue;

        $link_value = $site_a_tag->getAttribute('href');


    }

    //TODO save links to DB
}

function sitemap() {
    //TODO refresh sitemap
    //TODO display map to user
    //TODO display map to admin
}


//Remove table upon plugin deactivation
register_deactivation_hook( __FILE__, 'wpmedia_uninstall' );

function wpmedia_uninstall() {

    global $wpdb;

    $table = $wpdb->prefix . 'wpmedia_site_crawl';

    $sql = "DROP TABLE IF EXISTS $table;";

    $wpdb->query($sql);

    delete_option('my_plugin_db_version');

}
