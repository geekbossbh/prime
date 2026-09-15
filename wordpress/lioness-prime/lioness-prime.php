<?php
/**
 * Plugin Name: Lioness Prime — Course Page
 * Description: Serves the Lioness Prime Course enrollment page, emails the invoice to the
 *              buyer and to Lioness Prime when a payment is confirmed, and records every
 *              enrollment under Enrollments in the admin. Payment screenshots are kept out
 *              of the media library and served only to signed-in staff.
 * Version:     2.0.0
 * Author:      Lioness Prime
 * License:     GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* -------------------------------------------------------------------------
 * Settings. Override any of these in wp-config.php.
 * ---------------------------------------------------------------------- */

/** Primary address for enrollment mail. Comma-separate for more than one. */
if ( ! defined( 'LIONESS_MERCHANT_EMAIL' ) ) {
	define( 'LIONESS_MERCHANT_EMAIL', 'hi@prime.aasaad.com' );
}
/** Second address copied on everything, always as Bcc. */
if ( ! defined( 'LIONESS_INVOICE_COPY' ) ) {
	define( 'LIONESS_INVOICE_COPY', 'angel.lionness@gmail.com' );
}
/** Address invoices are sent from. Must be on a domain this site may send for. */
if ( ! defined( 'LIONESS_FROM_EMAIL' ) ) {
	define( 'LIONESS_FROM_EMAIL', 'hi@prime.aasaad.com' );
}

/**
 * The price, decided here rather than in the page.
 *
 * The browser cannot be trusted with what something costs: anyone can edit the
 * page before submitting. These are the figures that reach the invoice, and the
 * served page is rewritten to match them, so this file is the only place the
 * price lives.
 */
if ( ! defined( 'LIONESS_PRICE_USD' ) ) {
	define( 'LIONESS_PRICE_USD', '150.00' );
}
if ( ! defined( 'LIONESS_PRICE_BHD' ) ) {
	define( 'LIONESS_PRICE_BHD', '50' );
}
if ( ! defined( 'LIONESS_COURSE_NAME' ) ) {
	define( 'LIONESS_COURSE_NAME', 'Lioness Prime Course — Subscription' );
}

/** Largest payment screenshot accepted, in megabytes. */
if ( ! defined( 'LIONESS_MAX_PROOF_MB' ) ) {
	define( 'LIONESS_MAX_PROOF_MB', 6 );
}
/** Invoices one visitor may trigger per hour. */
if ( ! defined( 'LIONESS_RATE_LIMIT' ) ) {
	define( 'LIONESS_RATE_LIMIT', 5 );
}
/** Invoices the whole site may send per hour, whatever the source. */
if ( ! defined( 'LIONESS_GLOBAL_LIMIT' ) ) {
	define( 'LIONESS_GLOBAL_LIMIT', 40 );
}
/** Largest enrollment request accepted, in kilobytes, before anything parses it. */
if ( ! defined( 'LIONESS_MAX_REQUEST_KB' ) ) {
	define( 'LIONESS_MAX_REQUEST_KB', 9 * 1024 );
}
/** Shortest gap between two enrollments from one visitor, in seconds. */
if ( ! defined( 'LIONESS_MIN_GAP' ) ) {
	define( 'LIONESS_MIN_GAP', 8 );
}
/** Total megabytes of payment screenshots to keep before refusing new ones. */
if ( ! defined( 'LIONESS_PROOF_QUOTA_MB' ) ) {
	define( 'LIONESS_PROOF_QUOTA_MB', 400 );
}
/** Failed logins from one address before it is locked out, and for how long. */
if ( ! defined( 'LIONESS_LOGIN_TRIES' ) ) {
	define( 'LIONESS_LOGIN_TRIES', 5 );
}
if ( ! defined( 'LIONESS_LOGIN_LOCKOUT' ) ) {
	define( 'LIONESS_LOGIN_LOCKOUT', 900 );
}
/** Serve the course page as the front page. False leaves only /course. */
if ( ! defined( 'LIONESS_TAKE_FRONT_PAGE' ) ) {
	define( 'LIONESS_TAKE_FRONT_PAGE', true );
}
/** Origins allowed to post enrollments. Defaults to this site alone. */
if ( ! defined( 'LIONESS_ALLOWED_ORIGINS' ) ) {
	define( 'LIONESS_ALLOWED_ORIGINS', '' );
}
/** Send a content security policy with the page. */
if ( ! defined( 'LIONESS_SEND_CSP' ) ) {
	define( 'LIONESS_SEND_CSP', true );
}
/** Close the REST endpoints that list users and media to strangers. */
if ( ! defined( 'LIONESS_HARDEN_REST' ) ) {
	define( 'LIONESS_HARDEN_REST', true );
}
/** Turn off XML-RPC, a standing brute-force target. */
if ( ! defined( 'LIONESS_DISABLE_XMLRPC' ) ) {
	define( 'LIONESS_DISABLE_XMLRPC', true );
}

/* -------------------------------------------------------------------------
 * Site hardening
 * ---------------------------------------------------------------------- */

/**
 * Refuse an oversized enrollment before WordPress reads or decodes the body.
 * Runs on muplugins_loaded, the earliest hook available to a normal plugin, so a
 * multi-megabyte POST costs a header check rather than a JSON parse.
 */
add_action( 'muplugins_loaded', function () {
	if ( empty( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== strtoupper( (string) $_SERVER['REQUEST_METHOD'] ) ) {
		return;
	}
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
	if ( false === strpos( $uri, '/lioness/v1/' ) ) {
		return;
	}
	$length = isset( $_SERVER['CONTENT_LENGTH'] ) ? (int) $_SERVER['CONTENT_LENGTH'] : 0;
	if ( $length > LIONESS_MAX_REQUEST_KB * 1024 ) {
		status_header( 413 );
		header( 'Content-Type: application/json; charset=UTF-8' );
		echo wp_json_encode( array( 'ok' => false, 'error' => 'too_large' ) );
		exit;
	}
}, 0 );

/* -------------------------------------------------------------------------
 * Login throttling
 *
 * WordPress lets anyone guess passwords as fast as they can send requests.
 * ---------------------------------------------------------------------- */

function lioness_login_key() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : 'unknown';
	return 'lioness_login_' . md5( $ip );
}

add_filter( 'authenticate', function ( $user, $username ) {
	if ( empty( $username ) ) {
		return $user;   // an empty form, not an attempt
	}
	if ( (int) get_transient( lioness_login_key() ) >= LIONESS_LOGIN_TRIES ) {
		return new WP_Error(
			'lioness_locked',
			sprintf( 'Too many attempts. Try again in %d minutes.', (int) ceil( LIONESS_LOGIN_LOCKOUT / 60 ) )
		);
	}
	return $user;
}, 1, 2 );

add_action( 'wp_login_failed', function () {
	$key = lioness_login_key();
	set_transient( $key, (int) get_transient( $key ) + 1, LIONESS_LOGIN_LOCKOUT );
} );

add_action( 'wp_login', function () {
	delete_transient( lioness_login_key() );
} );

if ( LIONESS_HARDEN_REST ) {
	// A stranger can otherwise read every username from /wp-json/wp/v2/users,
	// and every uploaded file from /wp-json/wp/v2/media.
	add_filter( 'rest_endpoints', function ( $endpoints ) {
		if ( is_user_logged_in() ) {
			return $endpoints;
		}
		foreach ( array( '/wp/v2/users', '/wp/v2/users/(?P<id>[\d]+)', '/wp/v2/media', '/wp/v2/media/(?P<id>[\d]+)' ) as $route ) {
			unset( $endpoints[ $route ] );
		}
		return $endpoints;
	} );

	// /?author=1 otherwise redirects to the author archive and reveals the login name.
	add_action( 'template_redirect', function () {
		if ( ! is_admin() && isset( $_GET['author'] ) && ! is_user_logged_in() ) {
			wp_safe_redirect( home_url( '/' ), 301 );
			exit;
		}
	}, 1 );

	add_filter( 'oembed_response_data', function ( $data ) {
		unset( $data['author_name'], $data['author_url'] );
		return $data;
	} );
}

if ( LIONESS_DISABLE_XMLRPC ) {
	add_filter( 'xmlrpc_enabled', '__return_false' );
	add_filter( 'xmlrpc_methods', '__return_empty_array' );
}

// Don't advertise the exact WordPress version to someone shopping for exploits.
remove_action( 'wp_head', 'wp_generator' );
add_filter( 'the_generator', '__return_empty_string' );

// Never reveal whether it was the username or the password that was wrong.
add_filter( 'login_errors', function () {
	return 'Those details were not recognised.';
} );

/* -------------------------------------------------------------------------
 * Serving the page
 * ---------------------------------------------------------------------- */

/** True when the visitor asked for /course, with or without a trailing slash. */
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

	// Keep the displayed price and the invoiced price the same figure.
	$html = preg_replace( '/(\busd:\s*)[0-9]+(?:\.[0-9]+)?/', '${1}' . LIONESS_PRICE_USD, $html, 1 );
	$html = preg_replace( '/(\bbhd:\s*)[0-9]+(?:\.[0-9]+)?/', '${1}' . LIONESS_PRICE_BHD, $html, 1 );

	// The page is the same for everyone, so let caches and browsers keep it.
	// Under a flood the cheapest request is the one that never reaches PHP, and
	// the next cheapest is a 304.
	$stamp = (int) filemtime( $file );
	$etag  = '"lp-' . md5( $stamp . '|' . strlen( $html ) . '|' . LIONESS_PRICE_USD . '|' . LIONESS_PRICE_BHD ) . '"';

	$since = isset( $_SERVER['HTTP_IF_NONE_MATCH'] ) ? trim( (string) $_SERVER['HTTP_IF_NONE_MATCH'] ) : '';
	if ( '' !== $since && false !== strpos( $since, trim( $etag, '"' ) ) ) {
		status_header( 304 );
		header( 'ETag: ' . $etag );
		header( 'Cache-Control: public, max-age=300' );
		exit;
	}

	status_header( 200 );
	header( 'ETag: ' . $etag );
	header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s', $stamp ) . ' GMT' );
	header( 'Cache-Control: public, max-age=300' );
	header( 'Content-Type: text/html; charset=UTF-8' );
	header( 'X-Content-Type-Options: nosniff' );
	header( 'X-Frame-Options: SAMEORIGIN' );          // no framing the payment page
	header( 'Referrer-Policy: strict-origin-when-cross-origin' );
	header( 'Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=()' );

	if ( LIONESS_SEND_CSP ) {
		header( 'Content-Security-Policy: ' . implode( '; ', array(
			"default-src 'self'",
			"img-src 'self' data:",                                   // the screenshot preview is a data: URL
			"style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
			"font-src 'self' https://fonts.gstatic.com",
			"script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com",
			"connect-src 'self'",                                     // the invoice endpoint is same-origin
			"form-action 'self'",
			"frame-ancestors 'self'",
			"base-uri 'self'",
			'block-all-mixed-content',
		) ) );
	}

	echo $html; // phpcs:ignore WordPress.Security.EscapeOutput -- a whole HTML document, shipped with the plugin
	exit;
}, 0 );

/* -------------------------------------------------------------------------
 * Enrollment record
 * ---------------------------------------------------------------------- */

add_action( 'init', function () {
	register_post_type( 'lp_enrollment', array(
		'label'           => 'Enrollments',
		'public'          => false,      // never a front-end URL
		'publicly_queryable' => false,
		'exclude_from_search' => true,
		'show_in_rest'    => false,      // never served over the REST API
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
		'date'      => 'Received',
	);
} );

add_action( 'manage_lp_enrollment_posts_custom_column', function ( $col, $post_id ) {
	$get = function ( $k ) use ( $post_id ) {
		return esc_html( (string) get_post_meta( $post_id, $k, true ) );
	};
	if ( 'lp_who' === $col ) {
		echo $get( 'lp_name' ) . '<br><small>' . $get( 'lp_email' ) . ' &middot; ' . $get( 'lp_snapchat' ) . '</small>';
	} elseif ( 'lp_pay' === $col ) {
		$txn = $get( 'lp_transaction' );
		echo $get( 'lp_method' ) . '<br><small>' . $get( 'lp_amount' ) . ( '' !== $txn ? ' &middot; ' . $txn : '' ) . '</small>';
	} elseif ( 'lp_proof' === $col ) {
		$url = lioness_proof_url( $post_id );
		if ( '' !== $url ) {
			echo '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer"><img src="' . esc_url( $url )
				. '" alt="Payment screenshot" style="width:56px;height:56px;object-fit:cover;border:1px solid #ddd"></a>';
		} else {
			echo '<span style="color:#b3b3b3">none</span>';
		}
	}
}, 10, 2 );

/* -------------------------------------------------------------------------
 * Payment screenshots
 *
 * These are bank receipts: names, amounts, sometimes account numbers. They are
 * deliberately NOT put in the media library, because /wp-json/wp/v2/media lets
 * anyone list every attachment on a site. They live in a directory closed to the
 * web and are handed out only to signed-in staff.
 * ---------------------------------------------------------------------- */

function lioness_proof_dir() {
	$uploads = wp_upload_dir();
	$dir     = trailingslashit( $uploads['basedir'] ) . 'lioness-proofs';

	if ( ! file_exists( $dir ) ) {
		wp_mkdir_p( $dir );
	}
	// Belt and braces: deny at the web server, and leave nothing to index.
	$htaccess = $dir . '/.htaccess';
	if ( ! file_exists( $htaccess ) ) {
		file_put_contents( $htaccess,
			"Require all denied\n" .
			"<IfModule !mod_authz_core.c>\nOrder allow,deny\nDeny from all\n</IfModule>\n"
		);
	}
	$index = $dir . '/index.php';
	if ( ! file_exists( $index ) ) {
		file_put_contents( $index, "<?php\n// Silence is golden.\n" );
	}
	return $dir;
}

/** An admin-only URL for an enrollment's screenshot, or '' when there is none. */
function lioness_proof_url( $post_id ) {
	$file = (string) get_post_meta( $post_id, 'lp_proof_file', true );
	if ( '' === $file ) {
		return '';
	}
	return add_query_arg( array(
		'action'   => 'lioness_proof',
		'id'       => (int) $post_id,
		'_wpnonce' => wp_create_nonce( 'lioness_proof_' . (int) $post_id ),
	), admin_url( 'admin-ajax.php' ) );
}

add_action( 'wp_ajax_lioness_proof', function () {
	$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

	if ( ! $id || ! current_user_can( 'edit_post', $id ) || 'lp_enrollment' !== get_post_type( $id ) ) {
		status_header( 403 );
		exit;
	}
	if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'lioness_proof_' . $id ) ) {
		status_header( 403 );
		exit;
	}

	// basename() so a tampered meta value can never walk out of the directory.
	$name = basename( (string) get_post_meta( $id, 'lp_proof_file', true ) );
	$path = lioness_proof_dir() . '/' . $name;

	if ( '' === $name || ! is_readable( $path ) ) {
		status_header( 404 );
		exit;
	}
	$type = wp_check_filetype( $path );
	nocache_headers();
	header( 'Content-Type: ' . ( $type['type'] ? $type['type'] : 'application/octet-stream' ) );
	header( 'Content-Length: ' . filesize( $path ) );
	header( 'X-Content-Type-Options: nosniff' );
	header( 'Content-Disposition: inline; filename="' . $name . '"' );
	readfile( $path );
	exit;
} );

/**
 * Validates and stores the buyer's screenshot.
 *
 * Nothing the browser says about the file is believed. The bytes are decoded to
 * confirm they really are an image, and then re-encoded from the decoded pixels,
 * which discards anything hiding alongside them — a file that is both a valid
 * JPEG and a valid script cannot survive being redrawn.
 *
 * @return string|null The stored filename, or null when there is nothing usable.
 */
function lioness_store_proof( $b64, $ref ) {
	$b64 = preg_replace( '#^data:[^;]+;base64,#', '', trim( (string) $b64 ) );
	if ( '' === $b64 ) {
		return null;
	}
	// Base64 inflates by about a third; check the envelope before decoding it.
	if ( strlen( $b64 ) > LIONESS_MAX_PROOF_MB * 1024 * 1024 * 1.4 ) {
		return null;
	}
	$bytes = base64_decode( $b64, true );
	if ( false === $bytes || strlen( $bytes ) < 128 || strlen( $bytes ) > LIONESS_MAX_PROOF_MB * 1024 * 1024 ) {
		return null;
	}

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
	// A screenshot far larger than any phone screen is not a screenshot.
	if ( $info[0] > 6000 || $info[1] > 6000 ) {
		return null;
	}

	// Stop before the disk does.
	if ( lioness_proof_bytes_used() > LIONESS_PROOF_QUOTA_MB * 1024 * 1024 ) {
		return null;   // the enrollment is still recorded, just without the image
	}

	$ext   = $allowed[ $info['mime'] ];
	$clean = lioness_reencode_image( $bytes, $info['mime'] );
	if ( null !== $clean ) {
		$bytes = $clean;
	}

	$name = sprintf(
		'proof-%s-%s.%s',
		strtolower( preg_replace( '/[^A-Za-z0-9\-]/', '', (string) $ref ) ),
		wp_generate_password( 24, false, false ),
		$ext
	);
	$name = sanitize_file_name( $name );
	$path = lioness_proof_dir() . '/' . $name;

	if ( false === file_put_contents( $path, $bytes ) ) {
		return null;
	}
	@chmod( $path, 0640 );
	lioness_proof_bytes_used( strlen( $bytes ) );
	return $name;
}

/**
 * Running total of stored screenshots, kept as an option so nothing has to walk
 * the directory on every request. Pass a delta to adjust it.
 */
function lioness_proof_bytes_used( $delta = 0 ) {
	$used = (int) get_option( 'lioness_proof_bytes', 0 );
	if ( 0 !== $delta ) {
		$used = max( 0, $used + (int) $delta );
		update_option( 'lioness_proof_bytes', $used, false );
	}
	return $used;
}

/** Redraws an image from its decoded pixels, dropping everything else. */
function lioness_reencode_image( $bytes, $mime ) {
	if ( ! function_exists( 'imagecreatefromstring' ) ) {
		return null;   // no GD; the image was still validated above
	}
	$img = @imagecreatefromstring( $bytes );
	if ( ! $img ) {
		return null;
	}
	ob_start();
	if ( 'image/png' === $mime ) {
		imagealphablending( $img, false );
		imagesavealpha( $img, true );
		imagepng( $img, null, 6 );
	} elseif ( 'image/webp' === $mime && function_exists( 'imagewebp' ) ) {
		imagewebp( $img, null, 88 );
	} else {
		imagejpeg( $img, null, 88 );
	}
	$out = ob_get_clean();
	imagedestroy( $img );
	return ( is_string( $out ) && '' !== $out ) ? $out : null;
}

/** Removes the screenshot from disk when its enrollment is deleted. */
add_action( 'before_delete_post', function ( $post_id ) {
	if ( 'lp_enrollment' !== get_post_type( $post_id ) ) {
		return;
	}
	$name = basename( (string) get_post_meta( $post_id, 'lp_proof_file', true ) );
	if ( '' === $name ) {
		return;
	}
	$path = lioness_proof_dir() . '/' . $name;
	if ( is_file( $path ) ) {
		lioness_proof_bytes_used( -filesize( $path ) );
		@unlink( $path );
	}
} );

/* -------------------------------------------------------------------------
 * The enrollment endpoint
 * ---------------------------------------------------------------------- */

add_action( 'rest_api_init', function () {
	register_rest_route( 'lioness/v1', '/invoice', array(
		array(
			'methods'             => 'POST',
			'callback'            => 'lioness_handle_invoice',
			'permission_callback' => '__return_true',
		),
		array(
			'methods'             => 'OPTIONS',
			'callback'            => function () {
				return new WP_REST_Response( null, 204 );
			},
			'permission_callback' => '__return_true',
		),
	) );
} );

/** Origins permitted to post an enrollment: this site, plus any explicitly listed. */
function lioness_allowed_origins() {
	$origins = array( untrailingslashit( home_url() ), untrailingslashit( site_url() ) );
	foreach ( explode( ',', (string) LIONESS_ALLOWED_ORIGINS ) as $extra ) {
		$extra = untrailingslashit( trim( $extra ) );
		if ( '' !== $extra ) {
			$origins[] = $extra;
		}
	}
	return array_unique( array_filter( $origins ) );
}

/**
 * Answers only the origins we know about, rather than every site on the web.
 * The route never reads cookies and never authenticates, so it cannot be used
 * to act as a signed-in user.
 */
add_filter( 'rest_pre_serve_request', function ( $served, $result, $request ) {
	if ( 0 !== strpos( $request->get_route(), '/lioness/v1/' ) ) {
		return $served;
	}
	$allowed = lioness_allowed_origins();
	$origin  = untrailingslashit( (string) get_http_origin() );

	// WordPress core answers every origin on REST routes, so the header is set
	// unconditionally here to replace it: an unknown caller is told only this
	// site's own origin, which its browser will refuse to match.
	$grant = ( '' !== $origin && in_array( $origin, $allowed, true ) ) ? $origin : reset( $allowed );

	header( 'Access-Control-Allow-Origin: ' . esc_url_raw( $grant ) );
	header( 'Access-Control-Allow-Methods: POST, OPTIONS' );
	header( 'Access-Control-Allow-Headers: Content-Type' );
	header( 'Vary: Origin' );
	return $served;
}, 10, 3 );

function lioness_client_key() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : 'unknown';
	return 'lioness_rl_' . md5( $ip );
}

/**
 * Records the enrollment and sends the invoice.
 *
 * The message is assembled here from sanitised fields, and the recipients are
 * the buyer's own address plus a fixed list — a caller cannot choose who gets
 * mail or what it says, so this is not a relay someone else can send through.
 * What things cost is decided here too, not by the browser.
 */
function lioness_handle_invoice( WP_REST_Request $request ) {
	// One visitor, and then the whole site, per hour.
	$key   = lioness_client_key();
	$count = (int) get_transient( $key );
	if ( $count >= LIONESS_RATE_LIMIT ) {
		return new WP_REST_Response( array( 'ok' => false, 'error' => 'rate_limited' ), 429 );
	}
	// Somebody sending rubbish repeatedly is not a buyer.
	if ( (int) get_transient( $key . '_bad' ) >= 10 ) {
		return new WP_REST_Response( array( 'ok' => false, 'error' => 'rate_limited' ), 429 );
	}
	// And nobody enrolls twice in eight seconds.
	if ( get_transient( $key . '_gap' ) ) {
		return new WP_REST_Response( array( 'ok' => false, 'error' => 'too_fast' ), 429 );
	}
	set_transient( $key . '_gap', 1, LIONESS_MIN_GAP );
	$global = (int) get_transient( 'lioness_rl_global' );
	if ( $global >= LIONESS_GLOBAL_LIMIT ) {
		return new WP_REST_Response( array( 'ok' => false, 'error' => 'busy' ), 429 );
	}
	set_transient( $key, $count + 1, HOUR_IN_SECONDS );
	set_transient( 'lioness_rl_global', $global + 1, HOUR_IN_SECONDS );

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
		set_transient( $key . '_bad', (int) get_transient( $key . '_bad' ) + 1, HOUR_IN_SECONDS );
		return new WP_REST_Response( array( 'ok' => false, 'error' => 'invalid_input' ), 400 );
	}

	// Only these two methods exist, and each has one price. Anything the browser
	// sends about the amount or the course is ignored.
	$method = ( 'PayPal' === $clean( 'method', 40 ) ) ? 'PayPal' : 'Benefit Pay';
	$amount = ( 'PayPal' === $method )
		? '$' . LIONESS_PRICE_USD
		: LIONESS_PRICE_BHD . ' BHD';

	$proof_file = lioness_store_proof( (string) $request->get_param( 'proof_base64' ), $ref );

	$data = array(
		'name'        => $name,
		'email'       => $email,
		'snapchat'    => $clean( 'snapchat', 60 ),
		'reference'   => $ref,
		'invoice_no'  => 'INV-' . preg_replace( '/^LP-/', '', $ref ),
		'course'      => LIONESS_COURSE_NAME,
		'course_note' => $clean( 'course_note', 300 ),
		'amount'      => $amount,
		'method'      => $method,
		'transaction' => $clean( 'transaction', 80 ),
		'date'        => date_i18n( 'd M Y, H:i' ),
		'has_proof'   => (bool) $proof_file,
	);

	// Keep the record first, so an enrollment is never lost to a mail failure.
	$post_id = wp_insert_post( array(
		'post_type'   => 'lp_enrollment',
		'post_status' => 'publish',
		'post_title'  => $data['reference'] . ' — ' . $data['name'],
	), true );

	if ( $post_id && ! is_wp_error( $post_id ) ) {
		foreach ( array( 'name', 'email', 'snapchat', 'reference', 'amount', 'method', 'transaction' ) as $k ) {
			update_post_meta( $post_id, 'lp_' . $k, $data[ $k ] );
		}
		if ( $proof_file ) {
			update_post_meta( $post_id, 'lp_proof_file', $proof_file );
		}
	}

	$copies = lioness_copy_list();

	$sent_buyer = wp_mail(
		$email,
		sprintf( 'Your Lioness Prime Course invoice — %s', $data['reference'] ),
		lioness_invoice_html( $data, false ),
		lioness_headers( LIONESS_MERCHANT_EMAIL, $copies )
	);

	wp_mail(
		LIONESS_MERCHANT_EMAIL,
		sprintf( 'New enrollment — %s (%s)', $data['name'], $data['reference'] ),
		lioness_invoice_html( $data, true ),
		lioness_headers( $email, LIONESS_INVOICE_COPY ),
		$proof_file ? array( lioness_proof_dir() . '/' . $proof_file ) : array()
	);

	return new WP_REST_Response(
		array( 'ok' => (bool) $sent_buyer, 'reference' => $data['reference'] ),
		$sent_buyer ? 200 : 502
	);
}

function lioness_headers( $reply_to, $bcc = '' ) {
	$headers = array(
		'Content-Type: text/html; charset=UTF-8',
		sprintf( 'From: Lioness Prime <%s>', LIONESS_FROM_EMAIL ),
		sprintf( 'Reply-To: %s', $reply_to ),
	);
	$bcc = trim( (string) $bcc, " \t," );
	if ( '' !== $bcc ) {
		$headers[] = sprintf( 'Bcc: %s', $bcc );
	}
	return $headers;
}

/** Both of Lioness Prime's own addresses, as a Bcc list. */
function lioness_copy_list() {
	return implode( ', ', array_filter( array_map( 'trim', array(
		LIONESS_MERCHANT_EMAIL,
		LIONESS_INVOICE_COPY,
	) ) ) );
}

function lioness_invoice_html( array $d, $for_merchant ) {
	$row = function ( $k, $v ) {
		return '<tr><td style="padding:9px 0;color:#675D71;font-size:14px">' . esc_html( $k ) .
			'</td><td style="padding:9px 0;text-align:right;font-size:14px;color:#191220"><strong>' .
			esc_html( $v ) . '</strong></td></tr>';
	};

	$first = explode( ' ', $d['name'] );
	$intro = $for_merchant
		? '<p style="margin:0 0 18px;color:#53475D;font-size:15px">A new enrollment came in through the course page. The buyer has been sent this same invoice. Their payment screenshot is attached.</p>'
		: '<p style="margin:0 0 18px;color:#53475D;font-size:15px">Thank you for joining the Lioness Prime Course, ' . esc_html( $first[0] ) . '. Here is your invoice — please keep it. Your access is opened once we confirm the payment against your reference, normally within 24 hours.</p>';

	// The screenshot is never linked: that URL is only for signed-in staff.
	$proof_line = $d['has_proof'] ? $row( 'Proof of payment', $for_merchant ? 'Attached' : 'Received' ) : '';

	return '<div style="background:#F5F1F8;padding:28px 12px;font-family:Helvetica,Arial,sans-serif">'
		. '<div style="max-width:560px;margin:0 auto;background:#fff;border:1px solid #E1D8E8">'
		. '<div style="background:#2C1240;padding:22px 26px">'
		. '<div style="color:#fff;font-size:20px;letter-spacing:.02em">Lioness Prime</div>'
		. '<div style="color:#A8842C;font-size:11px;letter-spacing:.24em;text-transform:uppercase;margin-top:4px">Course Invoice</div>'
		. '</div><div style="padding:26px">' . $intro
		. '<table style="width:100%;border-collapse:collapse">'
		. $row( 'Invoice no.', $d['invoice_no'] )
		. $row( 'Date', $d['date'] )
		. $row( 'Reference', $d['reference'] )
		. $row( 'Item', $d['course'] )
		. ( '' !== $d['course_note']
			? '<tr><td colspan="2" style="padding:0 0 10px;color:#675D71;font-size:13px">' . esc_html( $d['course_note'] ) . '</td></tr>'
			: '' )
		. $row( 'Amount', $d['amount'] )
		. $row( 'Paid via', $d['method'] )
		. ( '' !== $d['transaction'] ? $row( 'Transaction no.', $d['transaction'] ) : '' )
		. $proof_line
		. '</table>'
		. '<div style="margin:20px 0 0;padding:16px 18px;background:#FAF6EA;border:1px solid #E8DCBE;font-size:13.5px;color:#6A5417">'
		. 'Status: payment submitted, pending confirmation. This invoice records the purchase; it is not confirmation that the funds have cleared.'
		. '</div>'
		. '<table style="width:100%;border-collapse:collapse;margin-top:22px;border-top:1px solid #E5E0EA">'
		. $row( 'Name', $d['name'] )
		. $row( 'Email', $d['email'] )
		. $row( 'Snapchat', $d['snapchat'] )
		. '</table>'
		. '</div></div></div>';
}
