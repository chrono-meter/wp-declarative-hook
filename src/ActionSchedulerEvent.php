<?php  // phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase
// phpcs:disable Squiz.Commenting
namespace ChronoMeter\WpDeclarativeHook;

#[\Attribute( \Attribute::TARGET_FUNCTION | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE )]
class ActionSchedulerEvent extends Action {

	public $interval;
	public $group;
	public $unique;

	public function __construct( $interval, string $name, $priority = 10, $args = null, $group = '', $unique = false ) {
		parent::__construct( $name, $priority, $args );
		$this->interval = $interval;
		$this->group    = $group;
		$this->unique   = $unique;
	}

	/**
	 * Load the Action Scheduler library.
	 */
	public static function load(): void {
		if ( ! function_exists( 'as_has_scheduled_action' ) ) {
			require_once __DIR__ . '/../action-scheduler/action-scheduler.php';
		}
	}

	public function install( $callable ): void {  // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.callableFound
		// Add the action.
		parent::install( $callable );

		static::load();

		// Schedule the event.
		if ( false === as_has_scheduled_action( $this->name ) ) {
			$interval = ( is_callable( $this->interval ) ) ? call_user_func( $this->interval ) : $this->interval;

			as_schedule_recurring_action( time() + 60, $interval, $this->name, group: $this->group, unique: $this->unique, priority: $this->priority );
		}
	}
}
