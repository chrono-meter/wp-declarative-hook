<?php
// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited
namespace ChronoMeter\WpDeclarativeHook;

#[\Attribute( \Attribute::TARGET_FUNCTION | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE )]
class ReplacePostEditor extends Action {

	public function __construct(
		/**
		 * The post type to replace the editor for.
		 *
		 * @var string|string[]|callable
		 */
		public $post_type,
		/**
		 * Whether to show screen options in upper right corner of the screen.
		 */
		public bool $show_screen_options = false,
		/**
		 * Call "wp-admin/admin-header.php".
		 */
		public bool $admin_header = true,
	) {
		$this->post_type = $post_type;
		$this->priority  = PHP_INT_MAX;

		add_filter(
			'replace_editor',
			function ( $result, $post ) {
				if ( $this->should_replace( $post ) ) {
					$result = true;
				}

				return $result;
			},
			10,
			2
		);

		parent::__construct( 'admin_action_edit' );
	}

	protected function should_replace( \WP_Post $post ): bool {
		return (
			in_array( ( $GLOBALS['pagenow'] ?? null ), array( 'post.php', 'post-new.php' ), true ) && 'edit' === ( $GLOBALS['action'] ?? null )
			&&
			$post
			&&
			( $post_type_object = get_post_type_object( $post->post_type ) )  // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.Found
			&&
			$post_type_object->show_ui
			&&
			(
				( is_string( $this->post_type ) && $post->post_type === $this->post_type )
				||
				( is_array( $this->post_type ) && in_array( $post->post_type, $this->post_type, true ) )
				||
				( is_callable( $this->post_type ) && call_user_func( $this->post_type, $post ) )
			)
			&&
			current_user_can( 'edit_post', $post->ID )
			&&
			'trash' !== $post->post_status
		);
	}

	public function install( $callable ): void {  // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.callableFound
		parent::install(
			/**
			 * Hard way for replacing editor.
			 *
			 * @link https://github.com/WordPress/wordpress-develop/blob/7.0.0/src/wp-admin/post.php#L119-L172
			 * @link https://github.com/WordPress/wordpress-develop/blob/7.0.0/src/wp-admin/edit-form-advanced.php#L14-L212
			 */
			function () use ( $callable ) {
				$post ??= ! empty( $_GET['post'] ) ? get_post( (int) $_GET['post'] ) : null;  // phpcs:ignore WordPress.Security.NonceVerification.Recommended

				if ( $this->should_replace( $post ) ) {
					global $post_type, $post_type_object, $parent_file, $submenu_file, $post_new_file, $title, $editing;

					$GLOBALS['post']  = $post;
					$post_type        = $post->post_type;
					$post_type_object = get_post_type_object( $post->post_type );

					if ( $post_type_object->show_in_menu && true !== $post_type_object->show_in_menu ) {
						$parent_file = $post_type_object->show_in_menu;
					} else {
						$parent_file = "edit.php?post_type=$post_type";
					}
					$submenu_file  = "edit.php?post_type=$post_type";
					$post_new_file = "post-new.php?post_type=$post_type";

					$title = $post_type_object->labels->edit_item;

					$editing = true;

					if ( ! $this->show_screen_options ) {
						add_filter( 'screen_options_show_screen', '__return_false' );
					}

					if ( $this->admin_header ) {
						require_once ABSPATH . 'wp-admin/admin-header.php';
					}

					$callable( $post );
				}
			}
		);
	}

	public static function render_full_height_style( string $selector ): void {
		?>
		<style>
			#adminmenuback {
				bottom: 0;
			}
			#wpbody-content {
				padding-bottom: 0px;
			}
			<?php echo $selector;  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> {
				overflow: auto;
				width: 100%;
				height: calc(100vh - var(--wp-admin--admin-bar--height, 0px));
				border: none;
			}
			#wpcontent > :not(#wpadminbar, #wpbody),
			#wpbody-content > :not(<?php echo $selector;  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>),
			#wpfooter {
				display: none;
			}
		</style>
		<?php
	}
}
