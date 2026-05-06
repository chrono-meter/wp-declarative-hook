<?php  // phpcs:ignore
// phpcs:disable Squiz.Commenting

namespace ChronoMeter\WpDeclarativeHook;

#[\Attribute( \Attribute::TARGET_FUNCTION | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE )]
class PostCapFilter extends Hook {

	/**
	 * The name of capability.
	 *
	 * @var string
	 */
	public string $cap;

	/**
	 * The priority of the hook.
	 * Default is 10.
	 *
	 * @var int
	 */
	public int $priority;

	/**
	 * The constructor.
	 *
	 * @param string   $cap     The name of capability.
	 * @param int      $priority The priority of the hook.
	 */
	public function __construct( $cap, $priority = 10 ) {
		$this->cap      = $cap;
		$this->priority = (int) $priority;
	}

	public function install( $callable ): void {  // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.callableFound
		add_filter(
			'user_has_cap',
			function ( array $allcaps, array $caps, array $args, \WP_User $user ) use ( &$callable ): array {
				@list( $cap, $user_id, $post_id ) = $args;  // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

				if ( $cap === $this->cap && ! empty( $post_id ) ) {
					$post = get_post( (int) $post_id );

					if ( $post ) {
						$result = $callable( $post, $user );

						if ( null !== $result ) {
							$allcaps[ $cap ] = (bool) $result;
						}
					}
				}

				return $allcaps;
			},
			$this->priority,
			4
		);
	}
}
