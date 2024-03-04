<?php

namespace CBAppApi\API;

use CBAppApi\API\AppBaseRoute;
use WP_Error;
use WP_REST_Request;
use WP_REST_Server;
use WP_REST_Response;
use WP_Application_Passwords;


class AppUtilsRoute extends AppBaseRoute {


	/**
	 * The base of this controller's route.
	 *
	 * @var string
	 */
	protected $rest_base = 'apputils';

	/**
	 * Name prefix for app key.
	 *
	 * @var string
	 */
	protected $client_name = 'api-for-cb-app';

	/**
	 * Client uuid if not provided (not implemented yet).
	 *
	 * @var string
	 */
	protected $fallback_client_uuid = '4e9df002-97d9-40e7-bba6-ec341c75d4e3';

	/**
	 * Register the routes for the objects of the controller.
	 */
	public function register_routes() {
		$namespace = self::ROUTE_BASE . '/v' . self::ROUTE_VERSION;

		register_rest_route(
			$namespace,
			'/' . $this->rest_base . '/register',
			array(
				array(
					'methods'  => 'POST',
					'callback' => array( $this, 'register' ),
					'args'     => array(
						'user_name' => array(
							'required'          => true,
							'validate_callback' => function ( $param, $request, $key ) {
								return ! empty( $param );
							},
							'sanitize_callback' => function ( $param, $request, $key ) {
								return sanitize_user( $param );
							},
						),
						'password'  => array(
							'required'          => true,
							'validate_callback' => function ( $param, $request, $key ) {
								return ! empty( $param );
							},
							'sanitize_callback' => function ( $param, $request, $key ) {
								return sanitize_text_field( $param );
							},
						),
						'client_id' => array(
							'required'          => true,
							'validate_callback' => function ( $param, $request, $key ) {
								return ! empty( $param );
							},
							'sanitize_callback' => function ( $param, $request, $key ) {
								return sanitize_text_field( $param );
							},
						),
					),
				),
			)
		);

		register_rest_route(
			$namespace,
			'/' . $this->rest_base . '/check_auth',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'check_auth' ),
					'args'                => array(),
					'permission_callback' => function () {
						return self::loggedInAppPasswordAllowed();
					},
				),
			)
		);

		$options = get_option( 'cb_app_settings', array() );
		if ( ! array_key_exists( 'cb_field_deny_cors', $options ) || $options['cb_field_deny_cors'] != 'denied' ) {

			register_rest_route(
				$namespace,
				'/' . $this->rest_base . '/media',
				array(
					array(
						'methods'  => WP_REST_Server::READABLE,
						'callback' => array( $this, 'media' ),
						'args'     => array(
							'post_id' => array(
								'required'          => true,
								'validate_callback' => function ( $param, $request, $key ) {
									return is_numeric( $param );
								},
							),
						),
					),
				)
			);

			register_rest_route(
				$namespace,
				'/' . $this->rest_base . '/site_icon',
				array(
					array(
						'methods'  => WP_REST_Server::READABLE,
						'callback' => array( $this, 'site_icon' ),
						'args'     => array(),
					),
				)
			);

			register_rest_route(
				$namespace,
				'/' . $this->rest_base . '/uploads',
				array(
					array(
						'methods'  => WP_REST_Server::READABLE,
						'callback' => array( $this, 'uploads' ),
						'args'     => array(
							'org_url' => array(
								'required' => true,
							),
						),
					),
				)
			);
		}
	}

	/**
	 * Retrieves site_icon
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function site_icon( $request ) {
		$request = new WP_REST_Request( 'GET', '/' );
		$request->set_query_params( array( '_fields' => 'site_icon' ) );
		$response = rest_do_request( $request );
		$server   = rest_get_server();
		$data     = $server->response_to_data( $response, false );
		if ( ! array_key_exists( 'site_icon', $data ) ) {
			return new WP_Error( 'item without image', __( 'No image for item', 'api-for-cb-app' ), array( 'status' => 404 ), 404 );
		}
		$feat_image = wp_get_attachment_metadata( $data['site_icon'] );
		if ( $feat_image === false ) {
			return new WP_Error( 'item without image', __( 'No image for item', 'api-for-cb-app' ), array( 'status' => 404 ), 404 );
		}

		return $this->sendThumbnailByMeta( $feat_image );

	}

	/**
	 * Retrieves uploads item
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function uploads( $request ) {
		$org_url = $request->get_param( 'org_url' );
		if ( $org_url == null ) {
			return new WP_Error( 'parameter error', __( 'missing org_url', 'api-for-cb-app' ), array( 'status' => 400 ), 400 );
		}

		$url_path = wp_parse_url( $org_url, PHP_URL_PATH );
		if ( empty( $url_path ) ) {
			return new WP_Error( 'parameter error', __( 'path missing', 'api-for-cb-app' ), array( 'status' => 400 ), 400 );
		}

		if ( ! function_exists( 'get_home_path' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		$path = get_home_path() . ltrim( $url_path, '/' );
		// $path = $this->get_home_path() . ltrim($url_path, '/');

		if ( ! file_exists( $path ) ) {
			return new WP_Error( 'file not found', __( 'no uploads file', 'api-for-cb-app' ), array( 'status' => 404 ), 404 );
		}

		return $this->send_image( $path, '' );
	}

	/**
	 * Retrieves featured image and sends as binary data
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|binary data
	 */
	public function media( $request ) {
		$item_id = $request->get_param( 'post_id' );
		if ( $item_id == null ) {
			return new WP_Error( 'parameter error', __( 'missing post_id', 'api-for-cb-app' ), array( 'status' => 400 ), 400 );
		}

		$attachment_id = get_post_thumbnail_id( $item_id );
		if ( $attachment_id === false ) {
			return new WP_Error( 'item without image', __( 'No image for item', 'api-for-cb-app' ), array( 'status' => 404 ), 404 );
		}

		$feat_image = wp_get_attachment_metadata( $attachment_id );
		return $this->sendThumbnailByMeta( $feat_image );
	}

	/**
	 * Finds thumbnail for featured image
	 *
	 * @param array $feat_image featured image Metadata.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	protected function sendThumbnailByMeta( $feat_image ) {
		$thumbnail = null;
		if ( ! array_key_exists( 'thumbnail', $feat_image['sizes'] ) ) {
			foreach ( $feat_image['sizes'] as $t ) {
				if ( $thumbnail == null || $t['width'] < $thumbnail['width'] ) {
					$thumbnail = $t;
				}
			}
		} else {
			$thumbnail = $feat_image['sizes']['thumbnail'];
		}
		if ( $thumbnail == null ) {
			return new WP_Error( 'item without image', __( 'No image for item', 'api-for-cb-app' ), array( 'status' => 404 ), 404 );
		}

		$img_base_path = pathinfo( $feat_image['file'], PATHINFO_DIRNAME );
		$upload_dir    = wp_upload_dir();
		$path          = trailingslashit( $upload_dir['basedir'] ) . trailingslashit( $img_base_path ) . $feat_image['sizes']['thumbnail']['file'];

		$mime_type = @$feat_image['sizes']['thumbnail']['mime-type'];

		if ( ! file_exists( $path ) ) {
			return new WP_Error( 'file not found', 'no thumbnail file', array( 'status' => 404 ), 404 );
		}

		return $this->send_image( $path, $mime_type );
	}


	/**
	 * Serves an image via the REST endpoint.
	 *
	 * By default, every REST response is passed through json_encode(), as the
	 * typical REST response contains JSON data.
	 *
	 * This method hooks into the REST server to return a binary image.
	 *
	 * @param string $path Absolute path to the image to serve.
	 * @param string $mime_type The image mime type.
	 *
	 * @return WP_REST_Response The REST response object to serve an image.
	 */
	public function send_image( $path, $mime_type ) {
		$response = new WP_REST_Response();

		if ( file_exists( $path ) ) {
			// Image exists, prepare a binary-data response.
			// $response->set_data( file_get_contents( $path ) );
			$response->set_data( $path );

			if ( empty( $mime_type ) ) {
				if ( function_exists( 'finfo_open' ) ) {
					$finfo     = finfo_open( FILEINFO_MIME_TYPE );
					$mime_type = finfo_file( $finfo, $path );
					finfo_close( $finfo );
				} else {
					$mime_type = mime_content_type( $path );
				}
				if ( empty( $mime_type ) ) {
					$mime_type = 'application/octet-stream';
				}
			}

			$size = filesize( $path );

			$origin = get_http_origin();
			$origin = ( 'null' !== $origin ) ? esc_url_raw( $origin ) : '*';

			$response->set_headers(
				array(
					'Access-Control-Allow-Origin'   => $origin,
					'Access-Control-Allow-Headers'  => '*',
					'Access-Control-Expose-Headers' => '*',
					'Content-Type'                  => $mime_type,
					'Content-Length'                => $size,
				)
			);

			// HERE → This filter will return our binary image!
			add_filter( 'rest_pre_serve_request', array( $this, 'do_send_image' ), 0, 2 );
		} else {
			// Return a simple "not-found" JSON response.
			$response->set_data( 'not-found' );
			$response->set_status( 404 );
		}

		return $response;
	}

	/**
	 * Action handler that is used by `serve_image()` to serve a binary image
	 * instead of a JSON string.
	 *
	 * @return bool Returns true, if the image was served; this will skip the
	 *              default REST response logic.
	 */
	public function do_send_image( $served, $result ) {

		try {
			readfile( $result->get_data() );
			return true;
		} catch ( \Exception $e ) {
			return $served;
		}
	}

	/**
	 * Registers Client
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function register( $request ) {

		$user_app_passwords = array();
		$user               = wp_authenticate( $request->get_param( 'user_name' ), $request->get_param( 'password' ) );

		if ( is_wp_error( $user ) ) {
			return new WP_Error( 'authentication error', $user->get_error_message(), array( 'status' => 401 ), 401 );
		} else {
			if ( ! self::appPasswordAllowed( $user ) ) {
				return new WP_Error( 'authentication error', 'Application Password not applicable', array( 'status' => 401 ), 401 );
			}
			$client_uuid = $this->client_name . '_' . $request->get_param( 'client_id' );
			if ( WP_Application_Passwords::application_name_exists_for_user( $user->ID, $client_uuid ) ) {
				$user_app_passwords = WP_Application_Passwords::get_user_application_passwords( $user->ID );
				foreach ( $user_app_passwords as $user_app_password ) {
					if ( $user_app_password['app_id'] == $client_uuid ) {
						WP_Application_Passwords::delete_application_password( $user->ID, $user_app_password['uuid'] );
					}
				}
			}
			$app_pw = WP_Application_Passwords::create_new_application_password(
				$user->ID,
				array(
					'name'   => $client_uuid,
					'app_id' => $client_uuid,
				)
			);
			if ( is_wp_error( $app_pw ) ) {
				return new WP_Error( 'unable to create app password', $app_pw->get_error_message(), array( 'status' => 401 ), 401 );
			}
		}
		$data = (object) array(
			'code'    => 'successfully generated app key',
			'message' => __( 'successfully generated app key', 'api-for-cb-app' ),
			'data'    => $app_pw[0],
		);

		$response = new WP_REST_Response( $data, 200 );
		return $response;
	}

	/**
	 * Check authentication
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function check_auth( $request ) {
		$data = (object) array(
			'code'    => 'authentication successfull',
			'message' => __( 'login successfully', 'api-for-cb-app' ),
			'data'    => null,
		);

		return new WP_REST_Response( $data, 200 );
	}
}
