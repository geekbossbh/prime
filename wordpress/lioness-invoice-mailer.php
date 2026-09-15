<?php
/**
 * Plugin Name: Lioness Prime — Invoice Mailer
 * Description: Emails the course invoice to the buyer and to Lioness Prime when someone
 *              confirms a payment on the enrollment page, and keeps a record of every
 *              enrollment under Enrollments in the admin menu. Uses this site's own mail
 *              (WP Mail SMTP), so no external mail service is involved.
 * Version:     1.0.0
 * Author:      Lioness Prime
 * License:     GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Where the merchant copy goes. Comma-separate to copy more than one address. */
if ( ! defined( 'LIONESS_MERCHANT_EMAIL' ) ) {
	define( 'LIONESS_MERCHANT_EMAIL', 'hi@prime.aasaad.com, angel.lionness@gmail.com' );
}
/** Address the invoice is sent from. Must be on a domain this site may send for. */
if ( ! defined( 'LIONESS_FROM_EMAIL' ) ) {
	define( 'LIONESS_FROM_EMAIL', 'hi@prime.aasaad.com' );
}
/** Most invoices one visitor may trigger per hour. */
if ( ! defined( 'LIONESS_RATE_LIMIT' ) ) {
	define( 'LIONESS_RATE_LIMIT', 6 );
}

/* -------------------------------------------------------------------------
 * Enrollment record
 * ---------------------------------------------------------------------- */

add_action( 'init', function () {
	register_post_type( 'lp_enrollment', array(
		'label'           => 'Enrollments',
		'public'          => false,
		'show_ui'         => true,
		'show_in_menu'    => true,
		'menu_icon'       => 'dashicons-tickets-alt',
		'capability_type' => 'post',
		'map_meta_cap'    => true,
		'supports'        => array( 'title' ),
	) );
} );

add_filter( 'manage_lp_enrollment_posts_columns', function ( $cols ) {
	return array(
		'cb'        => isset( $cols['cb'] ) ? $cols['cb'] : '',
		'title'     => 'Reference',
		'lp_who'    => 'Buyer',
		'lp_pay'    => 'Payment',
		'lp_amount' => 'Amount',
		'date'      => 'Received',
	);
} );

add_action( 'manage_lp_enrollment_posts_custom_column', function ( $col, $post_id ) {
	$get = function ( $k ) use ( $post_id ) {
		return esc_html( (string) get_post_meta( $post_id, $k, true ) );
	};
	if ( 'lp_who' === $col ) {
		echo $get( 'lp_name' ) . '<br><small>' . $get( 'lp_email' ) . ' &middot; ' . $get( 'lp_snapchat' ) .
			'<br>' . $get( 'lp_phone' ) . '</small>';
	} elseif ( 'lp_pay' === $col ) {
		$txn = $get( 'lp_transaction' );
		echo $get( 'lp_method' ) . ( '' !== $txn ? '<br><small>' . $txn . '</small>' : '' );
	} elseif ( 'lp_amount' === $col ) {
		echo $get( 'lp_amount' );
	}
}, 10, 2 );

/* -------------------------------------------------------------------------
 * REST endpoint
 * ---------------------------------------------------------------------- */

add_action( 'rest_api_init', function () {
	register_rest_route( 'lioness/v1', '/invoice', array(
		array(
			'methods'             => 'POST',
			'callback'            => 'lioness_handle_invoice',
			'permission_callback' => '__return_true',
		),
		// Browsers send a preflight before the POST.
		array(
			'methods'             => 'OPTIONS',
			'callback'            => function () {
				return new WP_REST_Response( null, 204 );
			},
			'permission_callback' => '__return_true',
		),
	) );
} );

/**
 * The enrollment page is served from a static host, so this one route answers
 * cross-origin requests. It never reads cookies and never authenticates, so it
 * cannot be used to act as a logged-in user.
 */
add_filter( 'rest_pre_serve_request', function ( $served, $result, $request ) {
	if ( 0 === strpos( $request->get_route(), '/lioness/v1/' ) ) {
		header( 'Access-Control-Allow-Origin: *' );
		header( 'Access-Control-Allow-Methods: POST, OPTIONS' );
		header( 'Access-Control-Allow-Headers: Content-Type' );
		header( 'Vary: Origin' );
	}
	return $served;
}, 10, 3 );

function lioness_client_key() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : 'unknown';
	return 'lioness_rl_' . md5( $ip );
}

/**
 * Builds and sends the invoice.
 *
 * Everything in the message is assembled here from sanitised fields, and the
 * recipients are the buyer's own address plus the fixed merchant copy — a caller
 * cannot choose who receives mail or what the body says, so the route is not a
 * relay someone else can send mail through.
 */
function lioness_handle_invoice( WP_REST_Request $request ) {
	// Simple per-visitor rate limit.
	$key   = lioness_client_key();
	$count = (int) get_transient( $key );
	if ( $count >= LIONESS_RATE_LIMIT ) {
		return new WP_REST_Response( array( 'ok' => false, 'error' => 'rate_limited' ), 429 );
	}
	set_transient( $key, $count + 1, HOUR_IN_SECONDS );

	// Bots fill hidden fields in; real visitors never see this one.
	if ( '' !== trim( (string) $request->get_param( 'website' ) ) ) {
		return new WP_REST_Response( array( 'ok' => true ), 200 );
	}

	$clean = function ( $k, $max = 200 ) use ( $request ) {
		return mb_substr( sanitize_text_field( (string) $request->get_param( $k ) ), 0, $max );
	};

	$email = sanitize_email( (string) $request->get_param( 'email' ) );
	$ref   = preg_replace( '/[^A-Z0-9\-]/', '', strtoupper( $clean( 'reference', 40 ) ) );
	$name  = $clean( 'customer_name', 120 );

	if ( ! is_email( $email ) || '' === $ref || '' === $name ) {
		return new WP_REST_Response( array( 'ok' => false, 'error' => 'invalid_input' ), 400 );
	}

	$data = array(
		'name'        => $name,
		'email'       => $email,
		'snapchat'    => $clean( 'snapchat', 60 ),
		'phone'       => $clean( 'phone', 40 ),
		'reference'   => $ref,
		'invoice_no'  => 'INV-' . preg_replace( '/^LP-/', '', $ref ),
		'course'      => $clean( 'course', 160 ),
		'course_note' => $clean( 'course_note', 300 ),
		'amount'      => $clean( 'amount', 40 ),
		'method'      => $clean( 'method', 40 ),
		'transaction' => $clean( 'transaction', 80 ),
		'date'        => date_i18n( 'd M Y, H:i' ),
	);

	// Keep the record first, so an enrollment is never lost to a mail failure.
	$post_id = wp_insert_post( array(
		'post_type'   => 'lp_enrollment',
		'post_status' => 'publish',
		'post_title'  => $data['reference'] . ' — ' . $data['name'],
	) );
	if ( $post_id && ! is_wp_error( $post_id ) ) {
		foreach ( array( 'name', 'email', 'snapchat', 'phone', 'reference', 'amount', 'method', 'transaction' ) as $k ) {
			update_post_meta( $post_id, 'lp_' . $k, $data[ $k ] );
		}
	}

	$sent_buyer = wp_mail(
		$email,
		sprintf( 'Your Lioness Prime Course invoice — %s', $data['reference'] ),
		lioness_invoice_html( $data, false ),
		lioness_headers( LIONESS_MERCHANT_EMAIL )
	);

	wp_mail(
		LIONESS_MERCHANT_EMAIL,
		sprintf( 'New enrollment — %s (%s)', $data['name'], $data['reference'] ),
		lioness_invoice_html( $data, true ),
		lioness_headers( $email )
	);

	return new WP_REST_Response( array( 'ok' => (bool) $sent_buyer, 'reference' => $data['reference'] ), $sent_buyer ? 200 : 502 );
}

function lioness_headers( $reply_to ) {
	return array(
		'Content-Type: text/html; charset=UTF-8',
		sprintf( 'From: Lioness Prime <%s>', LIONESS_FROM_EMAIL ),
		sprintf( 'Reply-To: %s', $reply_to ),
	);
}

function lioness_invoice_html( array $d, $for_merchant ) {
	$e   = 'esc_html';
	$row = function ( $k, $v ) {
		return '<tr><td style="padding:9px 0;color:#7A6E8C;font-size:14px">' . esc_html( $k ) .
			'</td><td style="padding:9px 0;text-align:right;font-size:14px;color:#1B1230"><strong>' .
			esc_html( $v ) . '</strong></td></tr>';
	};

	$intro = $for_merchant
		? '<p style="margin:0 0 18px;color:#4A3B62;font-size:15px">A new enrollment came in through the course page. The buyer has been sent this same invoice.</p>'
		: '<p style="margin:0 0 18px;color:#4A3B62;font-size:15px">Thank you for joining the Lioness Prime Course, ' . $e( explode( ' ', $d['name'] )[0] ) . '. Here is your invoice — please keep it. Your access is opened once we confirm the payment against your reference, normally within 24 hours.</p>';

	return '<div style="background:#F6F1FC;padding:28px 12px;font-family:Helvetica,Arial,sans-serif">'
		. '<div style="max-width:560px;margin:0 auto;background:#fff;border-radius:16px;overflow:hidden;border:1px solid #E7DBF6">'
		. '<div style="background:#3E1A63;padding:22px 26px">'
		. '<div style="color:#fff;font-size:20px;letter-spacing:.02em">Lioness Prime</div>'
		. '<div style="color:#C9A227;font-size:11px;letter-spacing:.24em;text-transform:uppercase;margin-top:4px">Course Invoice</div>'
		. '</div>'
		. '<div style="padding:26px">'
		. $intro
		. '<table style="width:100%;border-collapse:collapse">'
		. $row( 'Invoice no.', $d['invoice_no'] )
		. $row( 'Date', $d['date'] )
		. $row( 'Reference', $d['reference'] )
		. $row( 'Item', $d['course'] )
		. ( '' !== $d['course_note']
			? '<tr><td colspan="2" style="padding:0 0 10px;color:#7A6E8C;font-size:13px">' . esc_html( $d['course_note'] ) . '</td></tr>'
			: '' )
		. $row( 'Amount', $d['amount'] )
		. $row( 'Paid via', $d['method'] )
		. ( '' !== $d['transaction'] && '—' !== $d['transaction'] ? $row( 'Transaction no.', $d['transaction'] ) : '' )
		. '</table>'
		. '<div style="margin:20px 0 0;padding:16px 18px;background:#FCF7E8;border:1px solid #F1E4B9;border-radius:10px;font-size:13.5px;color:#6B540F">'
		. 'Status: payment submitted, pending confirmation. This invoice records the purchase; it is not confirmation that the funds have cleared.'
		. '</div>'
		. '<table style="width:100%;border-collapse:collapse;margin-top:22px;border-top:1px solid #ECE6F3">'
		. $row( 'Name', $d['name'] )
		. $row( 'Email', $d['email'] )
		. $row( 'Snapchat', $d['snapchat'] )
		. $row( 'Phone', $d['phone'] )
		. '</table>'
		. '</div></div></div>';
}
