<?php
/**
 Plugin Name: Industry
 Version: 0.0.1
 */


add_action( 'init', 'industry_function_of' );

/**
 * A function of great industry
 *
 * @since 0.0.1
 */
function industry_function_of(){
	new Industry();
	add_action( 'wp_enqueue_scripts', 'industry_genesis_client' );
}

class Industry {

	/**
	 * @var string
	 *
	 * @since 0.0.1
	 */
	protected $key = 'industry-flag';

	/**
	 * @var string
	 *
	 * @since 0.0.1
	 */
	protected $rest_key = 'rest';

	/**
	 * @var string
	 *
	 * @since 0.0.1
	 */
	protected $genesis_key = 'genesis';

	/**
	 * @var string
	 *
	 * @since 0.0.1
	 */
	protected $mode;

	/**
	 * Industry constructor.
	 *
	 * @since 0.0.1
	 */
	public function __construct() {

		$this->find_mode();
		if( is_string( $this->mode ) && $this->check_nonce() ){
			$this->engage();
		}

	}

	/**
	 * If possible, run system
	 *
	 * @since 0.0.1
	 */
	protected function engage(){
		switch( $this->mode ){
			case 'rest' :
				$this->engage_rest_mode();
				break;
			case 'genesis' :
				$this->engage_genesis_mode();
				break;
		}
	}

	/**
	 * Check the nonce
	 *
	 * @since 0.0.1
	 *
	 * @return bool
	 */
	protected function check_nonce(){
		if( isset( $_GET[ 'nonce' ] ) && wp_verify_nonce( $_GET[ 'nonce' ], 'industry' ) ){
			return true;
		}

		return false;
	}

	/**
	 * Attempt to turn on REST API mode
	 *
	 * @since 0.0.1
	 */
	protected function engage_rest_mode(){
		if( class_exists(  'WP_REST_Posts_Controller' ) ){
			include_once  __DIR__ . '/classes/REST.php';
			add_action( 'pre_get_posts', 'industry_rest', 999 );
		}
	}

	/**
	 * Attempt to turn on Genesis mode
	 *
	 * @since 0.0.1
	 */
	protected function engage_genesis_mode(){
		if( function_exists( 'genesis_custom_loop' ) ){
			add_action( 'pre_get_posts', 'industry_genesis', 999 );
		}
	}

	/**
	 * Return current mode
	 *
	 * @since 0.0.1
	 *
	 * @return string|null
	 */
	public function get_mode(){
		return $this->mode;
	}

	/**
	 * Set the mode
	 *
	 * @since 0.0.1
	 */
	protected function find_mode(){
		if ( isset( $_GET[ $this->key ] ) ) {
			if ( $this->genesis_key === $_GET[ $this->key ] ) {
				$this->mode = 'genesis';
			} elseif ( $this->rest_key == $_GET[ $this->key ] ) {
				$this->mode = 'rest';
			}
		}
	}
}

/**
 * Run our system if in REST API mode
 *
 * @since 0.0.1
 *
 * @uses "pre_get_posts" action
 *
 * @param WP_Query $query
 */
function industry_rest( WP_Query $query ){
	remove_action( 'pre_get_posts', __FUNCTION__, 999 );

	$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
	$request->set_attributes( [ 'context', 'industrial' ] );

	$industry  = new Industry_REST( 'post' );
	$response = $industry->create_post_response( $query, $request );
	industry_response_of( $response );


}

/**
 * Run our system if in Genesis mode
 *
 * @since 0.0.1
 *
 * @uses "pre_get_posts" action
 *
 * @param WP_Query $query
 */
function industry_genesis( WP_Query $query ){
	remove_action( 'pre_get_posts', __FUNCTION__, 999 );
	ob_start();
	genesis_custom_loop( $query->query );
	$html = ob_get_clean();

	$query->get_posts();
	$response = rest_ensure_response( [ 'html' => $html ] );
	$response->header( 'X-WP-Total', (int) $query->found_posts );
	$response->header( 'X-WP-TotalPages', (int) $query->max_num_pages );
	$response->header( 'content-type', 'application/json' );
	industry_response_of( $response );
}

/**
 * Server response and exit like a boss
 *
 * @since 0.0.1
 *
 * @param WP_REST_Response $response
 */
function industry_response_of( WP_REST_Response $response ){
	$response->header( 'X-INDUSTRY', 'apex' );
	/** @var WP_REST_Server $wp_rest_server */
	global $wp_rest_server;
	if( ! is_object( $wp_rest_server ) ){
		$wp_rest_server = new WP_REST_Server();
	}

	$wp_rest_server->send_headers(  $response->get_headers() );
	echo wp_json_encode( $response );
	exit;
}

/**
 * Load the JavaScript for Genesis
 *
 * @since 0.0.1
 *
 * @uses "wp_enqueue_scripts" action
 */
function industry_genesis_client(){
	if( function_exists( 'genesis_custom_loop' ) ){
		wp_enqueue_script( 'industry-genesis', plugin_dir_url(__FILE__) . 'industry-genesis.js', [ 'jquery' ] );
		wp_localize_script( 'industry-genesis', 'INDUSTRY', [
			'nonce' => [
				'key' => 'nonce',
				'value' => wp_create_nonce( 'industry' ),
			],
			'flag' => [
				'key' => 'industry-flag',
				'value' => 'genesis',
			],
			'url' => home_url()
		]);
	}

}