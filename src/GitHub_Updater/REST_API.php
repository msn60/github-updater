<?php
/**
 * GitHub Updater
 *
 * @author    Andy Fragen
 * @license   GPL-2.0+
 * @link      https://github.com/afragen/github-updater
 * @package   github-updater
 */

namespace Fragen\GitHub_Updater;

use Fragen\GitHub_Updater\Traits\GHU_Trait;
use Fragen\Singleton;

/**
 * Exit if called directly.
 */
if ( ! defined( 'WPINC' ) ) {
	die;
}

class REST_API {
	use GHU_Trait;

	/**
	 * Holds REST namespace.
	 *
	 * @var string
	 */
	public static $namespace = 'github-updater/v1';

	public function register_endpoints() {
		register_rest_route(
			self::$namespace,
			'test',
			[
				'methods'  => \WP_REST_Server::READABLE,
				'callback' => [ $this, 'test' ],
			]
		);

		register_rest_route(
			self::$namespace,
			'remote',
			[
				'methods'  => \WP_REST_Server::READABLE,
				'callback' => [ $this, 'get_remote_repo_data' ],
				'args'     => [
					'key' => [
						'default'           => null,
						'validate_callback' => 'sanitize_text_field',
					],
				],
			]
		);

		register_rest_route(
			self::$namespace,
			'update',
			[
				'methods'  => \WP_REST_Server::READABLE,
				'callback' => [ new REST_Update(), 'process_rest_request' ],
				'args'     => [
					'key'        => [
						'default'           => null,
						'validate_callback' => 'sanitize_text_field',
					],
					'plugin'     => [
						'default'           => null,
						'validate_callback' => 'sanitize_text_field',
					],
					'theme'      => [
						'default'           => null,
						'validate_callback' => 'sanitize_text_field',

					],
					'tag'        => [
						'default'           => 'master',
						'validate_callback' => 'sanitize_text_field',
					],
					'branch'     => [
						'default'           => null,
						'validate_callback' => 'sanitize_text_field',
					],
					'committish' => [
						'default'           => null,
						'validate_callback' => 'sanitize_text_field',
					],
					'override'   => [
						'default' => false,
					],
				],
			]
		);
	}

	public function test() {
		return 'test!';
	}

	/**
	 * Get repo data for Git Remote Updater.
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return string
	 */
	public function get_remote_repo_data( \WP_REST_Request $request ) {
		$ghu_plugins = Singleton::get_instance( 'Plugin', $this )->get_plugin_configs();
		$ghu_themes  = Singleton::get_instance( 'Theme', $this )->get_theme_configs();
		$ghu_tokens  = array_merge( $ghu_plugins, $ghu_themes );

		$site    = $request->get_header( 'host' );
		$api_url = add_query_arg(
			[
				'action' => 'github-updater-update',
				'key'    => $request->get_param( 'key' ),
			],
			\home_url( 'wp-json/' . self::$namespace . '/update/' )
			// admin_url( 'admin-ajax.php' )
		);
		foreach ( $ghu_tokens as $token ) {
			$slugs[] = [
				'slug'   => $token->slug,
				'type'   => $token->type,
				'branch' => $token->branch,
			];
		}
		$json = [
			'sites' => [
				'site'          => $site,
				'restful_start' => $api_url,
				'slugs'         => $slugs,
			],
		];

		// $json = json_encode( $json, JSON_FORCE_OBJECT );

		return $json;
	}
}
