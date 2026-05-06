<?php  // phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase
namespace ChronoMeter\WpDeclarativeHook;

trait HooksInstaller {

	/**
	 * Install the hooks for WordPress filters and actions.
	 */
	public static function install_static_methods(): void {
		Hook::install_static_methods( static::class );
	}

	/**
	 * Install the hooks for WordPress filters and actions.
	 */
	public function install_methods(): void {
		Hook::install_methods( $this );
	}
}
