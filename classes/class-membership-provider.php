<?php
/**
 * MaiExpoWP Membership Provider abstract class.
 *
 * Base class for membership provider implementations.
 *
 * @since 0.1.0
 *
 * @package MaiExpoWP
 */

namespace MaiExpoWP;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Abstract Membership_Provider class.
 *
 * @since 0.1.0
 */
abstract class Membership_Provider {

	/**
	 * Get the provider name.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	abstract public function get_name(): string;

	/**
	 * Check if this provider is available (plugin is active).
	 *
	 * @since 0.1.0
	 *
	 * @return bool
	 */
	abstract public function is_available(): bool;

	/**
	 * Get user's active memberships.
	 *
	 * @since 0.1.0
	 *
	 * @param int $user_id The user ID.
	 *
	 * @return array Array of membership data with keys: id, plan_id, name, status.
	 */
	abstract public function get_user_memberships( int $user_id ): array;

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
	abstract public function user_has_membership( int $user_id, int $plan_id ): bool;

	/**
	 * Get all available membership plans.
	 *
	 * @since 0.1.0
	 *
	 * @return array Array of plan data with keys: id, name.
	 */
	abstract public function get_membership_plans(): array;
}
