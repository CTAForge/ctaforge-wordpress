<?php
/**
 * WooCommerce integration — syncs customers and purchase events to CTAForge.
 *
 * Each WooCommerce action fires two calls to CTAForge:
 *   1. upsertContact()  — ensures the contact exists and is on the default list
 *   2. trackEvent()     — records the specific behavioral event with full properties
 *
 * Only loaded when WooCommerce is active (checked in Plugin.php).
 *
 * @package CTAForge\WooCommerce
 */

namespace CTAForge\WooCommerce;

use CTAForge\Api\Client;

/**
 * Hooks into WooCommerce order events to sync contact data and track events.
 */
class OrderSync {

	public function __construct() {
		add_action( 'woocommerce_checkout_order_created', [ $this, 'on_order_created' ] );
		add_action( 'woocommerce_order_status_completed',  [ $this, 'on_order_completed' ] );
		add_action( 'woocommerce_order_status_refunded',   [ $this, 'on_order_refunded' ] );
	}

	// ─── Checkout ────────────────────────────────────────────────────────────

	/**
	 * Fires when a new WooCommerce order is placed.
	 *
	 * Syncs the contact and records a `purchase` event with full order details.
	 *
	 * @param \WC_Order $order
	 */
	public function on_order_created( \WC_Order $order ): void {
		$email = $order->get_billing_email();
		if ( ! is_email( $email ) ) {
			return;
		}

		$settings = get_option( 'ctaforge_settings', [] );
		$list_id  = $settings['default_list'] ?? '';

		$client = Client::make();

		// 1. Ensure contact exists on the list.
		if ( ! empty( $list_id ) ) {
			$client->subscribe(
				$email,
				$list_id,
				[
					'firstName' => $order->get_billing_first_name(),
					'lastName'  => $order->get_billing_last_name(),
					'tags'      => [ 'woocommerce-customer' ],
					'customFields' => [
						'billing_country' => $order->get_billing_country(),
						'billing_city'    => $order->get_billing_city(),
					],
				]
			);
		}

		// 2. Track purchase event with rich order data.
		$client->track_event(
			$email,
			'purchase',
			[
				'order_id'     => $order->get_id(),
				'order_number' => $order->get_order_number(),
				'amount'       => $order->get_total(),
				'currency'     => $order->get_currency(),
				'status'       => $order->get_status(),
				'payment_method' => $order->get_payment_method_title(),
				'items'        => $this->get_order_items_summary( $order ),
				'coupon_codes' => $order->get_coupon_codes(),
				'site_url'     => get_site_url(),
			],
			'woocommerce'
		);
	}

	// ─── Order completed ─────────────────────────────────────────────────────

	/**
	 * Fires when an order status changes to "completed".
	 *
	 * @param int $order_id
	 */
	public function on_order_completed( int $order_id ): void {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$email = $order->get_billing_email();
		if ( ! is_email( $email ) ) {
			return;
		}

		$client = Client::make();

		// 1. Tag the contact as a verified buyer.
		$settings = get_option( 'ctaforge_settings', [] );
		if ( ! empty( $settings['default_list'] ) ) {
			$client->subscribe( $email, $settings['default_list'], [
				'tags' => [ 'woocommerce-purchased' ],
			] );
		}

		// 2. Track order_completed event.
		$client->track_event(
			$email,
			'order_completed',
			[
				'order_id'     => $order->get_id(),
				'order_number' => $order->get_order_number(),
				'amount'       => $order->get_total(),
				'currency'     => $order->get_currency(),
				'items'        => $this->get_order_items_summary( $order ),
			],
			'woocommerce'
		);
	}

	// ─── Order refunded ──────────────────────────────────────────────────────

	/**
	 * Fires when an order is refunded.
	 *
	 * @param int $order_id
	 */
	public function on_order_refunded( int $order_id ): void {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$email = $order->get_billing_email();
		if ( ! is_email( $email ) ) {
			return;
		}

		$client = Client::make();

		// 1. Tag the contact as refunded.
		$settings = get_option( 'ctaforge_settings', [] );
		if ( ! empty( $settings['default_list'] ) ) {
			$client->subscribe( $email, $settings['default_list'], [
				'tags' => [ 'woocommerce-refunded' ],
			] );
		}

		// 2. Track refund event.
		$refund_total = array_sum(
			array_map(
				fn( \WC_Order_Refund $r ) => (float) $r->get_amount(),
				$order->get_refunds()
			)
		);

		$client->track_event(
			$email,
			'refund',
			[
				'order_id'     => $order->get_id(),
				'order_number' => $order->get_order_number(),
				'refund_amount' => $refund_total,
				'currency'     => $order->get_currency(),
				'order_total'  => $order->get_total(),
			],
			'woocommerce'
		);
	}

	// ─── Helpers ─────────────────────────────────────────────────────────────

	/**
	 * Returns a compact array of items for event properties.
	 * Avoids sending excessive data — just name, sku, qty, price.
	 *
	 * @param  \WC_Order $order
	 * @return array
	 */
	private function get_order_items_summary( \WC_Order $order ): array {
		$items = [];
		foreach ( $order->get_items() as $item ) {
			/** @var \WC_Order_Item_Product $item */
			$product = $item->get_product();
			$items[] = [
				'name'     => $item->get_name(),
				'sku'      => $product ? $product->get_sku() : '',
				'quantity' => $item->get_quantity(),
				'total'    => $item->get_total(),
			];
		}
		return $items;
	}
}
