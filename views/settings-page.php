<?php
/**
 * Settings page view file.
 */
?>

<h1><?php esc_html_e( 'Site Crawl Settings', 'wp-media-site-crawl' ); ?></h1>
<h2><?php esc_html_e( 'Start a site crawl, view a sitemap, and show your sitemap to users.', 'wp-media-site-crawl' ); ?></h2>

<div>
	<p><?php esc_html_e( 'Clicking on Start Site Crawl will start a crawl of your site, and set a job to re-crawl your site every hour. This will create a sitemap that is visible to you and your users. At the start of each crawl, the old data will be removed and an entirely new set of data will be stored.' ); ?></p>
	<p><?php esc_html_e( 'After your initial crawl, a user visible sitemap can be found at', 'wp-media-site-crawl' ); ?> <a target="_blank" href="<?php echo esc_url( $sitemap_url ); ?>"><?php echo esc_html( $sitemap_url ); ?></a></p>

	<form method="POST">
		<?php
		// Add nonce field here for security. @link https://developer.wordpress.org/reference/functions/wp_nonce_field/
		?>
		<button type="submit" name="site-crawl"><?php esc_html_e( 'Start Site Crawl', 'wp-media-site-crawl' ); ?></button>
	</form>
	<?php
	// etc.....
	?>
