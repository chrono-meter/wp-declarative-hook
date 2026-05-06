<?php
namespace ChronoMeter\WpDeclarativeHook;

#[\Attribute( \Attribute::TARGET_FUNCTION | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE )]
class Filter extends Hook {

	/**
	 * The name of the hook.
	 *
	 * @var string
	 */
	public string $name;

	/**
	 * The priority of the hook.
	 * Default is 10.
	 *
	 * @var int
	 */
	public int $priority;

	/**
	 * The number of arguments the hook accepts.
	 * Default is the number of parameters the callable accepts.
	 *
	 * @var int|null
	 */
	public ?int $args;

	/**
	 * The constructor.
	 *
	 * @param string   $name     The name of the hook.
	 * @param int      $priority The priority of the hook.
	 * @param int|null $args     The number of arguments the hook accepts.
	 */
	public function __construct( $name, $priority = 10, $args = null ) {
		$this->name     = $name;
		$this->priority = (int) $priority;
		$this->args     = $args;
	}

	public function install( $callable ): void {  // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.callableFound
		$wrapped_callable = function ( ...$args ) use ( $callable ) {
			try {
				return $callable( ...$args );
			} catch ( \Throwable $e ) {
				do_action( 'wonolog.log.critical', $e );
			}
		};

		add_filter( $this->name, $wrapped_callable, $this->priority, null === $this->args ? $this->getNumberOfParameters( $callable ) : $this->args );
	}
}
