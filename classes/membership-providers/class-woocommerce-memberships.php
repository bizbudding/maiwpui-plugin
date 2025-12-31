<?php
/**
 * MaiWPUI WooCommerce Memberships provider.
 *
 * Membership provider for WooCommerce Memberships plugin.
 *
 * @since 0.1.0
 *
 * @package MaiWPUI
 */

namespace MaiWPUI\Providers;

use MaiWPUI\Membership_Provider;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * WooCommerce_Memberships class.
 *
 * @since 0.1.0
 */
class WooCommerce_Memberships extends Membership_Provider {

	/**
	 * Get the provider name.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'woocommerce-memberships';
	}

	/**
	 * Check if this provider is available.
	 *
	 * @since 0.1.0
	 *
	 * @return bool
	 */
	public function is_available(): bool {
		return function_exists( 'wc_memberships_get_user_memberships' );
	}

	/**
	 * Get user's active memberships.
	 *
	 * @since 0.1.0
	 *
	 * @param int $user_id The user ID.
	 *
	 * @return array
	 */
	public function get_user_memberships( int $user_id ): array {
		if ( ! $this->is_available() ) {
			return [];
		}

		$memberships      = [];
		$user_memberships = wc_memberships_get_user_memberships( $user_id );

		if ( empty( $user_memberships ) ) {
			return $memberships;
		}

		foreach ( $user_memberships as $membership ) {
			$plan = $membership->get_plan();

			if ( ! $plan ) {
				continue;
			}

			$memberships[] = [
				'id'      => $membership->get_id(),
				'plan_id' => $plan->get_id(),
				'name'    => $plan->get_name(),
				'status'  => $membership->get_status(),
			];
		}

		return $memberships;
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
		if ( ! $this->is_available() ) {
			return false;
		}

		if ( ! function_exists( 'wc_memberships_is_user_active_member' ) ) {
			return false;
		}

		return wc_memberships_is_user_active_member( $user_id, $plan_id );
	}

	/**
	 * Get all available membership plans.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	public function get_membership_plans(): array {
		if ( ! $this->is_available() ) {
			return [];
		}

		if ( ! function_exists( 'wc_memberships_get_membership_plans' ) ) {
			return [];
		}

		$plans            = [];
		$membership_plans = wc_memberships_get_membership_plans();

		foreach ( $membership_plans as $plan ) {
			$plans[] = [
				'id'   => $plan->get_id(),
				'name' => $plan->get_name(),
			];
		}

		return $plans;
	}
}
