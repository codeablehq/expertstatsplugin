<?php
/**
 *
 * @package wpcable
 */

/**
 * A Wrapper for the WordPress object cache
 *
 * Acts as a wrapper to the WordPress object cache
 *
 * @since      0.0.6
 * @package    wpcable
 * @see        https://codex.wordpress.org/Class_Reference/WP_Object_Cache
 * @author     Justin Frydman <justin.frydman@gmail.com>
 */
class wpcable_cache {

	/**
	 * The unique cache key to use
	 *
	 * @since    0.0.6
	 * @access   private
	 * @var      string    $cache_key    The unique cache key to use.
	 */
	private $cache_key;

	/**
	 * The cache iteration key
	 *
	 * @since    0.0.6
	 * @var      string    ITTR_KEY    The cache iteration
	 */
	const ITTR_KEY = 'wpcable_ittr';

	/**
	 * The query containing data we may need to cache
	 *
	 * @since    0.0.6
	 * @access   public
	 * @var      mixed    $query    The query containing data we may need to cache.
	 */
	private $query;

	/**
	 * The amount of seconds to store in the cache
	 *
	 * @since    0.0.6
	 * @access   public
	 * @var      int    $cache_expires    The amount of seconds to store in the cache.
	 */
	public $cache_expires;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    0.0.6
	 * @param    string $cache_key     The unique cache key to use.
	 */
	public function __construct( $cache_key = false, $query = false, $cache_expires = 0 ) {

		if ( ! $cache_key ) {
			throw new Exception( 'No cache key provided.' );
		}

		if ( $query ) {
			$this->query = $query;
		}

		if ( $cache_expires ) {
			$this->cache_expires = $cache_expires;
		}

		$this->cache_key = $cache_key . $this->get_cache_iteration();

	}

	/**
	 * Iterates the cache value
	 *
	 * @since    0.0.6
	 * @return   int     The current cache iteration
	 */
	private function get_cache_iteration() {

		$iteration = wp_cache_get( self::ITTR_KEY );

		if ( $iteration === false ) {
			wp_cache_set( self::ITTR_KEY, 1 );
			$iteration = 1;
		}

		return $iteration;
	}

	/**
	 * Returns the cached value for the provided key
	 *
	 * @since    0.0.6
	 * @return   mixed   The cache value
	 */
	public function get() {
		return wp_cache_get( $this->cache_key );
	}

	/**
	 * Set data in the object cache
	 *
	 * @since    0.0.6
	 * @param    $data   The data to cache
	 */
	public function set( $data ) {
		wp_cache_set( $this->cache_key, $data, null, $this->cache_expires );
	}

	/**
	 * Flush the cache by incrementing the cache iteration value
	 */
	public static function flush() {
		wp_cache_incr( self::ITTR_KEY );
	}

	/**
	 * Check the cache for data and prime it if needed
	 *
	 * @since    0.0.6
	 * @return   mixed
	 */
	public function check() {
		global $wpdb;

		if ( empty( $this->query ) ) {
			throw new Exception( 'You must provide a query in order to prime the cache' );
		}

		$data = $this->get();

		if ( $data === false ) {
			$data = $wpdb->get_results( $this->query, ARRAY_A );
			$this->set( $data );
		}

		return $data;
	}

}
