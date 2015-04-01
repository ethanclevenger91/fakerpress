<?php
namespace FakerPress;

class Ajax {

	public function __construct(){
		add_action( 'wp_ajax_' . Plugin::$slug . '.query_posts', array( __CLASS__, 'query_posts' ) );
		add_action( 'wp_ajax_' . Plugin::$slug . '.search_authors', array( __CLASS__, 'search_authors' ) );
	}

	public static function query_posts( $request = null ){
		$response = (object) array(
			'status' => false,
			'message' => __( 'Your request has failed', 'fakerpress' ),
			'results' => array(),
			'more' => true,
		);

		if ( ( ! Admin::$is_ajax && is_null( $request ) ) || ! is_user_logged_in() ){
			return ( Admin::$is_ajax ? exit( json_encode( $response ) ) : $response );
		}

		$request = (object) $_POST;

		if ( isset( $request->query['post_type'] ) && ! is_array( $request->query['post_type'] ) ){
			$request->query['post_type'] = array_map( 'trim', (array) explode( ',', $request->query['post_type'] ) );
		}

		$query = new \WP_Query( $request->query );

		if ( ! $query->have_posts() ){
			return ( Admin::$is_ajax ? exit( json_encode( $response ) ) : $response );
		}

		$response->status  = true;
		$response->message = __( 'Request successful', 'fakerpress' );

		foreach ( $query->posts as $k => $post ) {
			$query->posts[ $k ]->post_type = get_post_type_object( $post->post_type );
		}

		$response->results = $query->posts;

		if ( $query->max_num_pages >= $request->query['paged'] ){
			$response->more = false;
		}

		return ( Admin::$is_ajax ? exit( json_encode( $response ) ) : $response );
	}

	public static function search_authors( $request = null ){
		$response = (object) array(
			'status' => false,
			'message' => __( 'Your request has failed', 'fakerpress' ),
			'results' => array(),
			'more' => true,
		);

		if ( ( ! Admin::$is_ajax && is_null( $request ) ) || ! is_user_logged_in() ){
			return ( Admin::$is_ajax ? exit( json_encode( $response ) ) : $response );
		}

		$request = (object) wp_parse_args(
			$request,
			array(
				'search' => isset( $_POST['search'] ) ? $_POST['search'] : '',
				'page' => absint( isset( $_POST['page'] ) ? $_POST['page'] : 0 ),
				'page_limit' => absint( isset( $_POST['page_limit'] ) ? $_POST['page_limit'] : 10 ),
			)
		);

		$response->status  = true;
		$response->message = __( 'Request successful', 'fakerpress' );

		$query_args = array(
			'orderby' => 'display_name',
			'offset'  => $request->page_limit * ( $request->page - 1 ),
			'number'  => $request->page_limit,
		);

		if ( ! empty( $request->search ) ){
			$query_args['search'] = "*{$request->search}*";
			$query_args['search_columns'] = array(
				'user_login',
				'user_nicename',
				'user_email',
				'user_url',
			);
		}

		$users = new \WP_User_Query( $query_args );

		foreach ( $users->results as $result ){
			$response->results[] = $result;
		}

		if ( empty( $response->results ) || count( $response->results ) < $request->page_limit ){
			$response->more = false;
		}

		return ( Admin::$is_ajax ? exit( json_encode( $response ) ) : $response );
	}

}