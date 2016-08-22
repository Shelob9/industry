<?php

/**
 * Created by PhpStorm.
 * User: josh
 * Date: 8/21/16
 * Time: 9:22 PM
 */
class Industry_REST extends WP_REST_Posts_Controller {

	/**
	 * @param WP_Query $posts_query
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function create_post_response( WP_Query $posts_query, WP_REST_Request $request ) {

		$query_result = $posts_query->get_posts();

		{
			$posts = array();
			foreach ( $query_result as $post ) {
				if ( ! $this->check_read_permission( $post ) ) {
					continue;
				}

				$data    = $this->prepare_item_for_response( $post, $request );
				$posts[] = $this->prepare_response_for_collection( $data );
			}

			$page        = $posts_query->query_vars[ 'paged' ];
			$total_posts = $posts_query->found_posts;

			$max_pages = $posts_query->max_num_pages;

			$response = rest_ensure_response( $posts );
			$response->header( 'X-WP-Total', (int) $total_posts );
			$response->header( 'X-WP-TotalPages', (int) $max_pages );

			$request_params = $request->get_query_params();
			if ( ! empty( $request_params[ 'filter' ] ) ) {
				// Normalize the pagination params.
				unset( $request_params[ 'filter' ][ 'posts_per_page' ] );
				unset( $request_params[ 'filter' ][ 'paged' ] );
			}
			$base = add_query_arg( $request_params, rest_url( sprintf( '%s/%s', $this->namespace, $this->rest_base ) ) );

			if ( $page > 1 ) {
				$prev_page = $page - 1;
				if ( $prev_page > $max_pages ) {
					$prev_page = $max_pages;
				}
				$prev_link = add_query_arg( 'page', $prev_page, $base );
				$response->link_header( 'prev', $prev_link );
			}
			if ( $max_pages > $page ) {
				$next_page = $page + 1;
				$next_link = add_query_arg( 'page', $next_page, $base );
				$response->link_header( 'next', $next_link );
			}

			return $response;
		}
	}

}