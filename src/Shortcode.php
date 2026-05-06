<?php
namespace ChronoMeter\WpDeclarativeHook;

#[\Attribute( \Attribute::TARGET_FUNCTION | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE )]
class Shortcode extends Hook {

	/**
	 * The name of the shortcode.
	 *
	 * @var string
	 */
	public string $tag;

	/**
	 * Whether to capture the output of callback function.
	 *
	 * @var bool
	 */
	public bool $capture;

	/**
	 * The constructor.
	 *
	 * @param string   $tag     The name of the shortcode.
	 * @param bool     $capture Whether to capture the output of callback function. Default is false.
	 */
	public function __construct( $tag, $capture = false ) {
		$this->tag     = $tag;
		$this->capture = $capture;
	}

	public function install( $callable ): void {  // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.callableFound
		$wrapped_callable = function ( ...$args ) use ( $callable ) {
			try {
				if ( $this->capture ) {
					ob_start();
					$callable( ...$args );
					return ob_get_clean();
				} else {
					return $callable( ...$args );
				}
			} catch ( \Throwable $e ) {
				do_action( 'wonolog.log.critical', $e );
			}
		};

		add_shortcode( $this->tag, $wrapped_callable );
	}
}
