<?php  // phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase
// NOTE: This annotation is not useful, because there is no way to use tranlation functions in the annotation.
// phpcs:disable Squiz.Commenting
namespace ChronoMeter\WpDeclarativeHook;

#[\Attribute( \Attribute::TARGET_FUNCTION | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE )]
class MenuPage extends Hook {

	public string $page_title;
	public string $menu_title;
	public string $capability;
	public string $menu_slug;
	public string $icon_url;
	public ?int $position;

	public function __construct( $page_title, $menu_title, $capability, $menu_slug, $icon_url = '', $position = null ) {
		parent::__construct( $menu_slug );
		$this->page_title = $page_title;
		$this->menu_title = $menu_title;
		$this->capability = $capability;
		$this->menu_slug  = $menu_slug;
		$this->icon_url   = $icon_url;
		$this->position   = $position;
	}

	public function install( $callable ): void {  // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.callableFound
		add_action(
			'admin_menu',
			function () use ( $callable ) {
				add_menu_page(
					$this->page_title,
					$this->menu_title,
					$this->capability,
					$this->menu_slug,
					$callable,
					$this->icon_url,
					$this->position
				);
			}
		);
	}
}
