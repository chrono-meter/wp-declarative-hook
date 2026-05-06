<?php  // phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase
// phpcs:disable Squiz.Commenting
namespace ChronoMeter\WpDeclarativeHook;

#[\Attribute( \Attribute::TARGET_FUNCTION | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE )]
class ReplaceEditor extends Filter {

	/**
	 * The post type to replace the editor for.
	 *
	 * @var string|string[]|callable
	 */
	public $post_type;

	/**
	 * Whether to print wp-core like header.
	 *
	 * @var bool
	 */
	public bool $auto_header;

	/**
	 * Whether to wrap.
	 *
	 * @var bool
	 */
	public bool $wrap;

	public function __construct( $post_type, bool $auto_header = true, bool $wrap = true ) {
		parent::__construct( 'replace_editor' );
		$this->post_type   = $post_type;
		$this->auto_header = $auto_header;
		$this->wrap        = $wrap;
	}

	// todo edit lock handling
	public function install( $callable ): void {  // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.callableFound
		parent::install(
			function ( $result, $post ) use ( $callable ) {
				if (
					( is_string( $this->post_type ) && $post->post_type === $this->post_type )
					||
					( is_array( $this->post_type ) && in_array( $post->post_type, $this->post_type, true ) )
					||
					( is_callable( $this->post_type ) && call_user_func( $this->post_type, $post ) )
				) {
					$post = get_post( $post->ID, OBJECT, 'edit' );

					if ( $this->auto_header ) {
						static::mimic_wpcore_editor_header( $post );
					}

					if ( $this->wrap ) {
						echo '<div class="wrap">';
					}

					/**
					 * Notices are moved to after `.wp-header-end, .wrap h1, .wrap h2`.
					 * /var/www/html/wp-admin/js/common.js:855,1103
					 */
					$callable( $post );

					if ( $this->wrap ) {
						echo '</div>';
					}

					$result = true;
				}

				return $result;
			}
		);
	}

	// file:///var/www/html/wp-admin/edit-form-advanced.php
	// vscode://file/var/www/html/wp-admin/edit-form-advanced.php
	// /var/www/html/wp-admin/edit-form-advanced.php
	protected static function mimic_wpcore_editor_header( \WP_Post &$post ): void {
		global $post_type, $post_type_object, $action;

		// Flag that we're not loading the block editor.
		$current_screen = get_current_screen();
		$current_screen->is_block_editor( false );

		if ( is_multisite() ) {
			add_action( 'admin_footer', '_admin_notice_post_locked' );
		} else {
			$check_users = get_users(
				array(
					'fields' => 'ID',
					'number' => 2,
				)
			);

			if ( count( $check_users ) > 1 ) {
				add_action( 'admin_footer', '_admin_notice_post_locked' );
			}

			unset( $check_users );
		}

		$action = isset( $action ) ? $action : '';  // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		if ( (int) get_option( 'page_for_posts' ) === $post->ID && empty( $post->post_content ) ) {
			add_action( 'edit_form_after_title', '_wp_posts_page_notice' );
		}

		// Add the local autosave notice HTML.
		add_action( 'admin_footer', '_local_storage_notice' );

		if ( 'auto-draft' === $post->post_status ) {
			if ( 'edit' === $action ) {
				$post->post_title = '';
			}
		}

		$post_type_object = get_post_type_object( $post_type );  // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		require_once ABSPATH . 'wp-admin/admin-header.php';
	}
}
