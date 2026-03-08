<?php
/**
 * WooCommerce integration — syncs customers and purchase events to CTAForge.
 *
 * Only loaded when WooCommerce is active (checked in Plugin.php).
 *
 * @package CTAForge\WooCommerce
 */

namespace CTAForge\WooCommerce;

/**
 * Hooks into WooCommerce order events to sync contact data.
 */
class OrderSync {

	/** Tag applied to customers who have purchased. */
	const TAG_CUSTOMER = 'woocommerce-customer';

	public function __construct() {
		// Sync contact when an order is placed (any payment method).
		add_action( 'woocommerce_checkout_order_created', [ $this, 'on_order_created' ] );

		// Re-sync on status transitions of interest.
		add_action( 'woocommerce_order_status_completed', [ $this, 'on_order_completed' ] );
		add_action( 'woocommerce_order_status_refunded', [ $this, 'on_order_refunded' ] );
	}

	/**
	 * Fires when a new WooCommerce order is created at checkout.
	 *
	 * @param \WC_Order $order The WooCommerce order.
	 */
	public function on_order_created( \WC_Order $order ): void {
		$settings = get_option( 'ctaforge_settings', [] );
		$list_id  = $settings['default_list'] ?? '';

		if ( empty( $list_id ) ) {
			return;
		}

		$email = $order->get_billing_email();
		if ( ! is_email( $email ) ) {
			return;
		}

		$client = \CTAForge\Api\Client::make();
		$client->subscribe(
			$email,
			$list_id,
			[
				'firstName'    => $order->get_billing_first_name(),
				'lastName'     => $order->get_billing_last_name(),
				'tags'         => [ self::TAG_CUSTOMER ],
				'customFields' => [
					'last_order_id'    => (string) $order->get_id(),
					'last_order_total' => (string) $order->get_total(),
					'order_currency'   => $order->get_currency(),
				],
			]
		);
	}

	/**
	 * Tags the contact as a completed buyer when an order is fulfilled.
	 *
	 * @param int $order_id WooCommerce order ID.
	 */
	public function on_order_completed( int $order_id ): void {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$client = \CTAForge\Api\Client::make();
		$client->subscribe(
			$order->get_billing_email(),
			get_option( 'ctaforge_settings', [] )['default_list'] ?? '',
			[
				'tags' => [ 'woocommerce-purchased' ],
			]
		);
	}

	/**
	 * Tags the contact when an order is refunded.
	 *
	 * @param int $order_id WooCommerce order ID.
	 */
	public function on_order_refunded( int $order_id ): void {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$client = \CTAForge\Api\Client::make();
		$client->subscribe(
			$order->get_billing_email(),
			get_option( 'ctaforge_settings', [] )['default_list'] ?? '',
			[
				'tags' => [ 'woocommerce-refunded' ],
			]
		);
	}
}
