<?php
/**
 * MaiWPUI Restrict Content Pro provider.
 *
 * Membership provider for Restrict Content Pro plugin.
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
 * Restrict_Content_Pro class.
 *
 * @since 0.1.0
 */
class Restrict_Content_Pro extends Membership_Provider {

	/**
	 * Get the provider name.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'restrict-content-pro';
	}

	/**
	 * Check if this provider is available.
	 *
	 * @since 0.1.0
	 *
	 * @return bool
	 */
	public function is_available(): bool {
		return function_exists( 'rcp_get_customer_by_user_id' );
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

		$memberships = [];

		// Get customer.
		$customer = rcp_get_customer_by_user_id( $user_id );

		if ( ! $customer ) {
			return $memberships;
		}

		// Get customer memberships.
		$customer_memberships = rcp_get_customer_memberships(
			$customer->get_id(),
			[
				'status' => [ 'active', 'cancelled' ], // Include cancelled that still have access.
			]
		);

		if ( empty( $customer_memberships ) ) {
			return $memberships;
		}

		foreach ( $customer_memberships as $membership ) {
			// Skip if membership doesn't have access.
			if ( ! $membership->can_access() ) {
				continue;
			}

			$level_id   = $membership->get_object_id();
			$level_name = '';

			// Get membership level name.
			if ( function_exists( 'rcp_get_membership_level' ) ) {
				$level = rcp_get_membership_level( $level_id );
				if ( $level ) {
					$level_name = $level->get_name();
				}
			}

			$memberships[] = [
				'id'      => $membership->get_id(),
				'plan_id' => $level_id,
				'name'    => $level_name,
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
	 * @param int $plan_id The plan ID (membership level ID).
	 *
	 * @return bool
	 */
	public function user_has_membership( int $user_id, int $plan_id ): bool {
		if ( ! $this->is_available() ) {
			return false;
		}

		$customer = rcp_get_customer_by_user_id( $user_id );

		if ( ! $customer ) {
			return false;
		}

		// Get memberships for this specific level.
		$memberships = rcp_get_customer_memberships(
			$customer->get_id(),
			[
				'object_id' => $plan_id,
				'status'    => [ 'active', 'cancelled' ],
			]
		);

		foreach ( $memberships as $membership ) {
			if ( $membership->can_access() ) {
				return true;
			}
		}

		return false;
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

		if ( ! function_exists( 'rcp_get_membership_levels' ) ) {
			return [];
		}

		$plans  = [];
		$levels = rcp_get_membership_levels(
			[
				'status' => 'active',
			]
		);

		foreach ( $levels as $level ) {
			$plans[] = [
				'id'   => $level->get_id(),
				'name' => $level->get_name(),
			];
		}

		return $plans;
	}
}
