<?php
declare(strict_types=1);

/**
 * Customize hooks – editor stub
 * 
 * ⚠️ This file is NEVER loaded at runtime
 * It exists only for IDE / static analysis.
 */

/**
 * Fired when Stripe checkout session is created
 *
 * @param array $payload Stripe session data
 * @return array|null
 */
function onStripeCheckoutCreated(array $payload): ?array {}

/**
 * Fired when Stripe checkout session is completed (webhook)
 *
 * @param array $payload Stripe session data
 * @return array|null
 */
function onStripeWebhook(array $payload): ?array {}

/**
 * Fired when result page is rendered
 *
 * @param array $payload Stripe session data
 * @return void
 */
function onStripeResult(array $payload): void {}
