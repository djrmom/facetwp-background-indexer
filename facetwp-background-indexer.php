<?php
/*
Plugin Name: FacetWP Background Indexer
Plugin URI:
Description: Background Indexer for FacetWP
Version: 0.1
Text Domain: facetwp-background-indexer
Domain Path: /languages/
*/

class FacetWP_Background_Indexer {

	/**
	 * @var FWP_Indexer_Process
	 */
	protected $process_all;

	/**
	 * FacetWP_Background_Indexer constructor.
	 */
	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init' ) );
		add_action( 'admin_bar_menu', array( $this, 'admin_bar' ), 100 );
		add_action( 'init', array( $this, 'process_handler' ) );

	}

	/**
	 * Init
	 */
	public function init() {
		require_once plugin_dir_path( __FILE__ ) . 'wp-background-processing/classes/wp-background-process.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-indexer-process.php';

		$this->process_all    = new FWP_Indexer_Process();
	}

	/**
	 * Admin bar
	 *
	 * @param WP_Admin_Bar $wp_admin_bar
	 */
	public function admin_bar( $wp_admin_bar ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$wp_admin_bar->add_menu( array(
			'id'    => 'fwp-indexer',
			'title' => __( 'Index', 'fwp-indexer' ),
			'href'   => wp_nonce_url( admin_url( '?process=all'), 'process' ),
		) );
	}

	/**
	 * Process handler
	 */
	public function process_handler() {
		if ( ! isset( $_GET['process'] ) || ! isset( $_GET['_wpnonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'process') ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( 'all' === $_GET['process'] ) {
			$this->handle_all();
		}
	}

	/**
	 * Handle all
	 */
	protected function handle_all() {

		global $wpdb;

		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}facetwp_index" );

		$args = array(
			'post_type'         => 'any',
			'post_status'       => 'publish',
			'posts_per_page'    => -1,
			'fields'            => 'ids',
			'orderby'           => 'ID',
			'cache_results'     => false,
			'no_found_rows'     => true,
		);

		// Control which posts to index
		$args = apply_filters( 'facetwp_indexer_query_args', $args );

		// Loop through all posts
		$query = new WP_Query( $args );
		$post_ids = (array) $query->posts;

		foreach( $post_ids AS $post_id ) {
			$this->process_all->push_to_queue( $post_id );
		}

		error_log( 'FacetWP Background Indexing Start' );

		$this->process_all->save()->dispatch();
	}

}

new FacetWP_Background_Indexer();