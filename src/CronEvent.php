<?php
namespace ChronoMeter\WpDeclarativeHook;

#[\Attribute( \Attribute::TARGET_FUNCTION | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE )]
class CronEvent extends Action {

	public $interval;

	public function __construct( $interval, string $name, $priority = 10, $args = null ) {
		parent::__construct( $name, $priority, $args );
		$this->interval = $interval;
	}

	public function install( $callable ): void {  // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.callableFound
		parent::install( $callable );

		// Schedule the event.
		if ( ! wp_next_scheduled( $this->name ) ) {
			$interval = ( is_callable( $this->interval ) ) ? call_user_func( $this->interval ) : $this->interval;

			wp_schedule_single_event( time() + $interval, $this->name );
		}
	}
}
