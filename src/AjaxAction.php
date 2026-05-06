<?php  // phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase
// phpcs:disable Squiz.Commenting
namespace ChronoMeter\WpDeclarativeHook;

#[\Attribute( \Attribute::TARGET_FUNCTION | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE )]
class AjaxAction extends Action {

	public bool $nopriv       = false;
	public $capability        = null;
	public $nonce_action      = null;
	public string $nonce_name = '_wpnonce';

	public function __construct(
		string $name,
		$priority = 10,
		$args = null,
		bool $nopriv = false,
		$capability = null,
		$nonce_action = null,
		string $nonce_name = '_wpnonce'
	) {
		parent::__construct( $name, $priority, $args );
		$this->nopriv       = $nopriv;
		$this->capability   = $capability;
		$this->nonce_action = $nonce_action;
		$this->nonce_name   = $nonce_name;
	}

	public function install( $callable ): void {  // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.callableFound
		$wrapper = function ( ...$args ) use ( $callable ) {
			$cap = is_callable( $this->capability ) ? call_user_func( $this->capability ) : $this->capability;

			if ( $cap && ! current_user_can( $cap ) ) {
				wp_send_json_error( __( 'You need a higher level of permission.' ) );
			}

			$nonce_action = is_callable( $this->nonce_action ) ? call_user_func( $this->nonce_action ) : $this->nonce_action;

			if ( $nonce_action && ! check_ajax_referer( $nonce_action, $this->nonce_name, false ) ) {
				wp_send_json_error( __( 'Invalid parameter.' ) );
			}

			try {
				$result = $callable( ...$args );

				if ( ! is_wp_error( $result ) ) {
					wp_send_json_success( $result );
				} else {
					wp_send_json_error( $result->get_error_message() );
				}
			} catch ( \Throwable $e ) {
				wp_send_json_error( $e->getMessage() );
			}
		};

		add_action( 'wp_ajax_' . $this->name, $wrapper, $this->priority, null === $this->args ? $this->getNumberOfParameters( $callable ) : $this->args );

		if ( $this->nopriv ) {
			add_action( 'wp_ajax_nopriv_' . $this->name, $wrapper, $this->priority, null === $this->args ? $this->getNumberOfParameters( $callable ) : $this->args );
		}
	}
}
