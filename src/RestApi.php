<?php  // phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase
// phpcs:disable Squiz.Commenting
namespace ChronoMeter\WpDeclarativeHook;

#[\Attribute( \Attribute::TARGET_FUNCTION | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE )]
class RestApi extends Hook {
	protected static $routes = array();

	public string $route_namespace;
	public string $route;
	public array $args;
	public bool $override;

	public function __construct(
		string $route_namespace,
		string $route,
		array $args = array(),
		bool $override = false
	) {
		$this->route_namespace = $route_namespace;
		$this->route           = $route;
		$this->args            = $args;
		$this->override        = $override;
	}

	public function install( $callable ): void {  // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.callableFound
		static $called = false;

		if ( ! $called ) {
			add_action( 'rest_api_init', array( static::class, 'rest_api_init' ) );
			$called = true;
		}

		$args             = $this->args;
		$args['callback'] = function ( ...$args ) use ( $callable ) {
			try {
				return rest_ensure_response( $callable( ...$args ) );

			} catch ( WPError $e ) {
				return $e->wp_error;

			} catch ( \Throwable $e ) {
				// Return a WP_Error object from the callback.
				return new \WP_Error( 'rest_error', $e->getMessage(), array( 'status' => 500 ) );
			}
		};

		// Queue the route for registration.
		static::$routes[] = array(
			'route_namespace' => $this->route_namespace,
			'route'           => $this->route,
			'args'            => $args,
			'override'        => $this->override,
		);
	}

	/**
	 * Register queued routes.
	 */
	public static function rest_api_init() {
		foreach ( self::$routes as $route ) {
			register_rest_route( $route['route_namespace'], $route['route'], $route['args'], $route['override'] );
		}
	}
}
