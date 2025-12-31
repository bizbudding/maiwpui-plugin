<?php
/**
 * MaiWPUI Membership Manager class.
 *
 * Manages membership providers and aggregates membership data.
 *
 * @since 0.1.0
 *
 * @package MaiWPUI
 */

namespace MaiWPUI;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Membership_Manager class.
 *
 * @since 0.1.0
 */
class Membership_Manager {

	/**
	 * Instance.
	 *
	 * @since 0.1.0
	 *
	 * @var Membership_Manager|null
	 */
	private static ?Membership_Manager $instance = null;

	/**
	 * Registered providers.
	 *
	 * @since 0.1.0
	 *
	 * @var Membership_Provider[]
	 */
	private array $providers = [];

	/**
	 * Get instance.
	 *
	 * @since 0.1.0
	 *
	 * @return Membership_Manager
	 */
	public static function get_instance(): Membership_Manager {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	private function __construct() {
		$this->register_default_providers();
	}

	/**
	 * Register default membership providers.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	private function register_default_providers(): void {
		// Register WooCommerce Memberships provider.
		$this->register_provider( new Providers\WooCommerce_Memberships() );

		// Register Restrict Content Pro provider.
		$this->register_provider( new Providers\Restrict_Content_Pro() );

		// Allow additional providers to be registered.
		do_action( 'maiwpui_register_membership_providers', $this );
	}

	/**
	 * Register a membership provider.
	 *
	 * @since 0.1.0
	 *
	 * @param Membership_Provider $provider The provider instance.
	 *
	 * @return void
	 */
	public function register_provider( Membership_Provider $provider ): void {
		$this->providers[ $provider->get_name() ] = $provider;
	}

	/**
	 * Get all registered providers.
	 *
	 * @since 0.1.0
	 *
	 * @return Membership_Provider[]
	 */
	public function get_providers(): array {
		return $this->providers;
	}

	/**
	 * Get available (active) providers.
	 *
	 * @since 0.1.0
	 *
	 * @return Membership_Provider[]
	 */
	public function get_available_providers(): array {
		return array_filter(
			$this->providers,
			fn( Membership_Provider $provider ) => $provider->is_available()
		);
	}

	/**
	 * Get user memberships from all available providers.
	 *
	 * @since 0.1.0
	 *
	 * @param int $user_id The user ID.
	 *
	 * @return array Contains 'memberships' array and 'is_peopletak' boolean.
	 */
	public function get_user_memberships( int $user_id ): array {
		$all_memberships = [];

		foreach ( $this->get_available_providers() as $provider ) {
			$memberships = $provider->get_user_memberships( $user_id );

			foreach ( $memberships as $membership ) {
				$membership['provider'] = $provider->get_name();
				$all_memberships[]      = $membership;
			}
		}

		// Get plan IDs.
		$plan_ids = array_column( $all_memberships, 'plan_id' );

		// Check for PeopleTek membership.
		$peopletak_plan_ids = $this->get_peopletak_plan_ids();
		$is_peopletak       = ! empty( array_intersect( $plan_ids, $peopletak_plan_ids ) );

		return [
			'memberships'  => $all_memberships,
			'is_peopletak' => $is_peopletak,
		];
	}

	/**
	 * Check if user has any membership from any provider.
	 *
	 * @since 0.1.0
	 *
	 * @param int $user_id The user ID.
	 *
	 * @return bool
	 */
	public function user_has_any_membership( int $user_id ): bool {
		foreach ( $this->get_available_providers() as $provider ) {
			$memberships = $provider->get_user_memberships( $user_id );

			if ( ! empty( $memberships ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if user has a specific membership plan.
	 *
	 * @since 0.1.0
	 *
	 * @param int $user_id The user ID.
	 * @param int $plan_id The plan ID.
	 *
	 * @return bool
	 */
	public function user_has_membership( int $user_id, int $plan_id ): bool {
		foreach ( $this->get_available_providers() as $provider ) {
			if ( $provider->user_has_membership( $user_id, $plan_id ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if user is a PeopleTek member.
	 *
	 * @since 0.1.0
	 *
	 * @param int $user_id The user ID.
	 *
	 * @return bool
	 */
	public function is_peopletak_member( int $user_id ): bool {
		$data = $this->get_user_memberships( $user_id );

		return $data['is_peopletak'];
	}

	/**
	 * Get PeopleTek plan IDs.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	public function get_peopletak_plan_ids(): array {
		/**
		 * Filter the PeopleTek plan IDs.
		 *
		 * @since 0.1.0
		 *
		 * @param array $plan_ids Array of plan IDs that identify PeopleTek members.
		 */
		return apply_filters( 'maiwpui_peopletak_plan_ids', [ 7176 ] );
	}

	/**
	 * Get all available membership plans from all providers.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	public function get_all_membership_plans(): array {
		$all_plans = [];

		foreach ( $this->get_available_providers() as $provider ) {
			$plans = $provider->get_membership_plans();

			foreach ( $plans as $plan ) {
				$plan['provider'] = $provider->get_name();
				$all_plans[]      = $plan;
			}
		}

		return $all_plans;
	}
}
