<?php  // phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase
/**
 * Add WordPress filters and actions by method annotations.
 */
// phpcs:disable Squiz.Commenting
namespace ChronoMeter\WpDeclarativeHook;

abstract class Hook {
	/**
	 * Install the hook.
	 *
	 * @param callable $callable The callable to install.
	 */
	abstract public function install( $callable ): void;  // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.callableFound

	/**
	 * Class names that have been installed.
	 *
	 * @var string[]
	 */
	public static $installed_classes = array();

	protected function getNumberOfParameters( $callable ): int {  // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid, Universal.NamingConventions.NoReservedKeywordParameterNames.callableFound
		if ( is_array( $callable ) ) {
			$reflection = new \ReflectionMethod( $callable[0], $callable[1] );

		} elseif ( is_object( $callable ) && ! $callable instanceof \Closure ) {
			$reflection = new \ReflectionMethod( $callable, '__invoke' );

		} else {
			$reflection = new \ReflectionFunction( $callable );
		}

		return $reflection->getNumberOfParameters();
	}

	protected static function raw_install_methods( $target, \ReflectionClass $reflection, ?int $filter = null ): void {
		foreach ( $reflection->getMethods( $filter ) as $method ) {
			foreach ( $method->getAttributes() as $attribute ) {
				$instance = $attribute->newInstance();

				if ( $instance instanceof self ) {  // DON'T compare with static.
					$instance->install( array( $target, $method->getName() ) );
				}
			}
		}
	}

	public static function install_static_methods( $class, bool $force = false ): void {  // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.classFound
		if ( ! $force && static::is_installed_class( $class ) ) {
			return;
		}

		static::raw_install_methods( $class, new \ReflectionClass( $class ), \ReflectionMethod::IS_STATIC );

		static::$installed_classes[] = $class;
	}

	public static function is_installed_class( string $class ): bool {  // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.classFound
		return in_array( $class, static::$installed_classes, true );
	}

	public static function assert_class_installed( string $class ): void {  // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.classFound
		if ( ! static::is_installed_class( $class ) ) {
			wp_die( 'Class not installed: ' . $class );  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	public static function install_methods( $object ): void {  // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.objectFound
		static::raw_install_methods( $object, new \ReflectionObject( $object ), \ReflectionMethod::IS_PUBLIC );
	}
}
