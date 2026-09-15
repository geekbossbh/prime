<?php
/**
 * Plugin Name: Lioness Prime — Course Page
 * Description: Serves the Lioness Prime Course enrollment page at the site's front page
 *              and at /course, emails the invoice to the buyer and to Lioness Prime when a
 *              payment is confirmed, and records every enrollment under Enrollments in the
 *              admin menu. Mail goes out through this site (WP Mail SMTP), so no external
 *              mail service is involved.
 * Version:     1.1.0
 * Author:      Lioness Prime
 * License:     GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Where the merchant copy goes. Comma-separate to copy more than one address. */
if ( ! defined( 'LIONESS_MERCHANT_EMAIL' ) ) {
	define( 'LIONESS_MERCHANT_EMAIL', 'hi@prime.aasaad.com' );
}
/**
 * Address copied on every invoice, the buyer's and the merchant's alike.
 * Sent as Bcc so buyers never see it; change 'Bcc' to 'Cc' in lioness_headers()
 * below if you would rather it were visible on the message.
 */
if ( ! defined( 'LIONESS_INVOICE_COPY' ) ) {
	define( 'LIONESS_INVOICE_COPY', 'angel.lionness@gmail.com' );
}
/** Address the invoice is sent from. Must be on a domain this site may send for. */
if ( ! defined( 'LIONESS_FROM_EMAIL' ) ) {
	define( 'LIONESS_FROM_EMAIL', 'hi@prime.aasaad.com' );
}
/** Largest payment screenshot accepted, in megabytes. */
if ( ! defined( 'LIONESS_MAX_PROOF_MB' ) ) {
	define( 'LIONESS_MAX_PROOF_MB', 10 );
}
/** Most invoices one visitor may trigger per hour. */
if ( ! defined( 'LIONESS_RATE_LIMIT' ) ) {
	define( 'LIONESS_RATE_LIMIT', 6 );
}
/** Serve the course page as the site's front page. Set to false to only use /course. */
if ( ! defined( 'LIONESS_TAKE_FRONT_PAGE' ) ) {
	define( 'LIONESS_TAKE_FRONT_PAGE', true );
}

/* -------------------------------------------------------------------------
 * Serving the page
 * ---------------------------------------------------------------------- */

/** True when the visitor asked for /course (with or without a trailing slash). */
function lioness_is_course_path() {
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
	$path = trim( (string) wp_parse_url( $uri, PHP_URL_PATH ), '/' );
	return 'course' === strtolower( $path );
}

add_action( 'template_redirect', function () {
	if ( is_admin() || wp_doing_ajax() || is_feed() || is_robots() ) {
		return;
	}
	if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'GET' !== strtoupper( (string) $_SERVER['REQUEST_METHOD'] ) ) {
		return;
	}
	$wanted = lioness_is_course_path() || ( LIONESS_TAKE_FRONT_PAGE && is_front_page() );
	if ( ! $wanted ) {
		return;
	}

	$file = plugin_dir_path( __FILE__ ) . 'page/index.html';
	if ( ! is_readable( $file ) ) {
		return;   // fall through to the theme rather than showing a blank page
	}
	$html = file_get_contents( $file );
	if ( false === $html ) {
		return;
	}

	// The page refers to its images relatively; point them at the plugin folder.
	$base = plugin_dir_url( __FILE__ ) . 'page/';
	$html = str_replace( array( '"assets/', "'assets/" ), array( '"' . $base . 'assets/', "'" . $base . 'assets/' ), $html );

	status_header( 200 );
	nocache_headers();
	header( 'Content-Type: text/html; charset=UTF-8' );
	echo $html; // phpcs:ignore WordPress.Security.EscapeOutput -- a whole HTML document, shipped with the plugin
	exit;
}, 0 );

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
		'lp_proof'  => 'Proof',
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
	} elseif ( 'lp_proof' === $col ) {
		$url = (string) get_post_meta( $post_id, 'lp_proof_url', true );
		if ( '' !== $url ) {
			echo '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener"><img src="' . esc_url( $url )
				. '" alt="Payment screenshot" style="width:56px;height:56px;object-fit:cover;border-radius:6px"></a>';
		} else {
			echo '<span style="color:#b3b3b3">none</span>';
		}
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
		'proof_url'   => '',
		'date'        => date_i18n( 'd M Y, H:i' ),
	);

	// The payment screenshot. Never trust what the browser says it is: the bytes
	// are decoded and identified here, and anything that is not a real JPEG, PNG
	// or WebP is dropped rather than written to disk.
	$proof = lioness_store_proof(
		(string) $request->get_param( 'proof_base64' ),
		$data['reference']
	);
	$data['proof_url'] = $proof ? (string) $proof['url'] : '';

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
		if ( $proof ) {
			update_post_meta( $post_id, 'lp_proof_url', $proof['url'] );
			update_post_meta( $post_id, 'lp_proof_id', $proof['id'] );
			wp_update_post( array( 'ID' => $proof['id'], 'post_parent' => $post_id ) );
			set_post_thumbnail( $post_id, $proof['id'] );
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
		lioness_headers( $email ),
		$proof ? array( $proof['path'] ) : array()
	);

	return new WP_REST_Response( array( 'ok' => (bool) $sent_buyer, 'reference' => $data['reference'] ), $sent_buyer ? 200 : 502 );
}

/**
 * Saves the buyer's payment screenshot into the media library.
 *
 * @param string $b64 Raw base64 payload from the request (no data: prefix).
 * @param string $ref The enrollment reference, used only to name the file.
 * @return array|null { id, url, path } or null when there is nothing usable.
 */
function lioness_store_proof( $b64, $ref ) {
	$b64 = preg_replace( '#^data:[^;]+;base64,#', '', trim( $b64 ) );
	if ( '' === $b64 ) {
		return null;
	}
	// Base64 inflates by about a third; check before decoding.
	if ( strlen( $b64 ) > LIONESS_MAX_PROOF_MB * 1024 * 1024 * 1.4 ) {
		return null;
	}
	$bytes = base64_decode( $b64, true );
	if ( false === $bytes || strlen( $bytes ) < 128 ) {
		return null;
	}
	if ( strlen( $bytes ) > LIONESS_MAX_PROOF_MB * 1024 * 1024 ) {
		return null;
	}

	// Identify the image from its own content, not from anything the caller said.
	$info = @getimagesizefromstring( $bytes );
	if ( ! $info || empty( $info['mime'] ) ) {
		return null;
	}
	$allowed = array(
		'image/jpeg' => 'jpg',
		'image/png'  => 'png',
		'image/webp' => 'webp',
	);
	if ( ! isset( $allowed[ $info['mime'] ] ) ) {
		return null;
	}

	$name   = sprintf( 'proof-%s-%s.%s', strtolower( $ref ), wp_generate_password( 8, false, false ), $allowed[ $info['mime'] ] );
	$upload = wp_upload_bits( sanitize_file_name( $name ), null, $bytes );
	if ( ! empty( $upload['error'] ) || empty( $upload['file'] ) ) {
		return null;
	}

	$attachment_id = wp_insert_attachment( array(
		'post_mime_type' => $info['mime'],
		'post_title'     => sprintf( 'Payment proof %s', $ref ),
		'post_status'    => 'inherit',
	), $upload['file'] );

	if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
		return null;
	}
	require_once ABSPATH . 'wp-admin/includes/image.php';
	wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $upload['file'] ) );

	return array(
		'id'   => $attachment_id,
		'url'  => $upload['url'],
		'path' => $upload['file'],
	);
}

function lioness_headers( $reply_to ) {
	$headers = array(
		'Content-Type: text/html; charset=UTF-8',
		sprintf( 'From: Lioness Prime <%s>', LIONESS_FROM_EMAIL ),
		sprintf( 'Reply-To: %s', $reply_to ),
	);
	if ( '' !== trim( LIONESS_INVOICE_COPY ) ) {
		$headers[] = sprintf( 'Bcc: %s', LIONESS_INVOICE_COPY );
	}
	return $headers;
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
		. ( '' !== $d['proof_url'] ? $row( 'Proof of payment', 'Attached' ) : '' )
		. '</table>'
		. ( '' !== $d['proof_url']
			? '<div style="margin-top:18px"><div style="font-size:11px;letter-spacing:.18em;text-transform:uppercase;color:#7A6E8C;margin-bottom:8px">Payment screenshot</div>'
				. '<a href="' . esc_url( $d['proof_url'] ) . '"><img src="' . esc_url( $d['proof_url'] )
				. '" alt="Payment screenshot" style="max-width:100%;border-radius:10px;border:1px solid #ECE6F3"></a></div>'
			: '' )
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
