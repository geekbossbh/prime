<?php
/**
 * Lioness Prime — core, version 2.2.2.
 *
 * The version lives in this FILENAME on purpose. A server with OPcache set to
 * skip timestamp checks will keep running the bytecode it compiled for a given
 * path, so replacing a file's contents can leave the old code running with no
 * sign of it. A filename the server has never compiled cannot be stale, and the
 * loader picks the newest core it finds, so an upload takes effect immediately
 * without anyone having to flush a cache.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LIONESS_VERSION', '2.2.2' );

/**
 * The plugin's own directory and URL.
 *
 * This file lives in core/, so __FILE__ points one level too deep — these are
 * resolved from the parent so the page and its images are found wherever the
 * core sits.
 */
define( 'LIONESS_DIR', trailingslashit( dirname( __DIR__ ) ) );
define( 'LIONESS_URL', trailingslashit( plugins_url( '', dirname( __DIR__ ) . '/lioness-prime.php' ) ) );

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

/** Where the plugin looks for its own updates. */
if ( ! defined( 'LIONESS_UPDATE_MANIFEST' ) ) {
	define( 'LIONESS_UPDATE_MANIFEST',
		'https://raw.githubusercontent.com/geekbossbh/prime/refs/heads/claude/affectionate-bell-7uot8x/dist/update.json' );
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
 * Settings
 *
 * Every constant above is a fallback. Anything set on the settings screen wins,
 * so prices, addresses and course wording can change without touching a file.
 * An empty setting falls back rather than blanking the site.
 * ---------------------------------------------------------------------- */

function lioness_opt( $key, $fallback = '' ) {
	$saved = get_option( 'lioness_settings', array() );
	if ( ! is_array( $saved ) || ! isset( $saved[ $key ] ) ) {
		return $fallback;
	}
	$value = is_string( $saved[ $key ] ) ? trim( $saved[ $key ] ) : $saved[ $key ];
	return ( '' === $value ) ? $fallback : $value;
}

function lioness_price_usd()      { return (string) lioness_opt( 'price_usd', LIONESS_PRICE_USD ); }
function lioness_price_bhd()      { return (string) lioness_opt( 'price_bhd', LIONESS_PRICE_BHD ); }
function lioness_course_name()    { return (string) lioness_opt( 'course_name', LIONESS_COURSE_NAME ); }
function lioness_course_note()    { return (string) lioness_opt( 'course_note', '' ); }
function lioness_merchant_email() { return (string) lioness_opt( 'merchant_email', LIONESS_MERCHANT_EMAIL ); }
function lioness_copy_email()     { return (string) lioness_opt( 'copy_email', LIONESS_INVOICE_COPY ); }
function lioness_from_email()     { return (string) lioness_opt( 'from_email', LIONESS_FROM_EMAIL ); }
function lioness_paypal_link()    { return (string) lioness_opt( 'paypal_link', '' ); }
function lioness_logo_url()       { return (string) lioness_opt( 'logo_url', '' ); }
function lioness_qr_url()         { return (string) lioness_opt( 'qr_url', '' ); }
function lioness_proof_required() { return '0' !== (string) lioness_opt( 'proof_required', '1' ); }
function lioness_auto_update()    { return '0' !== (string) lioness_opt( 'auto_update', '1' ); }

/* -------------------------------------------------------------------------
 * Site hardening
 * ---------------------------------------------------------------------- */

/**
 * Refuse an oversized enrollment before WordPress reads or decodes the body.
 *
 * Deliberately called at file scope rather than hooked: a plugin in plugins/ is
 * loaded after muplugins_loaded has already fired, so hooking it there would
 * never run. Being included is the earliest a normal plugin can act.
 */
function lioness_guard_request_size() {
	if ( empty( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== strtoupper( (string) $_SERVER['REQUEST_METHOD'] ) ) {
		return;
	}
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
	if ( false === strpos( $uri, '/lioness/v1/' ) ) {
		return;
	}
	$length = isset( $_SERVER['CONTENT_LENGTH'] ) ? (int) $_SERVER['CONTENT_LENGTH'] : 0;
	if ( $length > LIONESS_MAX_REQUEST_KB * 1024 ) {
		header( 'HTTP/1.1 413 Payload Too Large' );
		header( 'Content-Type: application/json; charset=UTF-8' );
		echo '{"ok":false,"error":"too_large"}';
		exit;
	}
}
lioness_guard_request_size();

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

	$file = LIONESS_DIR . 'page/index.html';
	if ( ! is_readable( $file ) ) {
		return;   // fall through to the theme rather than showing a blank page
	}
	$html = file_get_contents( $file );
	if ( false === $html ) {
		return;
	}

	// The page refers to its images relatively; point them at the plugin folder.
	$base = LIONESS_URL . 'page/';
	$html = str_replace( array( '"assets/', "'assets/" ), array( '"' . $base . 'assets/', "'" . $base . 'assets/' ), $html );

	$html = lioness_apply_settings_to_page( $html, $base );

	// The page is the same for everyone, so let caches and browsers keep it.
	// Under a flood the cheapest request is the one that never reaches PHP, and
	// the next cheapest is a 304.
	$stamp = (int) filemtime( $file );
	$etag  = '"lp-' . md5( $stamp . '|' . strlen( $html ) . '|' . LIONESS_VERSION . '|'
		. wp_json_encode( get_option( 'lioness_settings', array() ) ) ) . '"';

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
	header( 'X-Lioness: ' . LIONESS_VERSION );   // so the running version can be checked from outside
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

/**
 * Writes the current settings into the page as it is served.
 *
 * Everything a shop owner is likely to want changed — the price, the course
 * wording, the PayPal link, the logo, the QR — is replaced here rather than
 * baked into the file, so changing any of it is a form submission and not a
 * re-upload. Each replacement is independent: if a pattern ever stops matching,
 * that one value keeps whatever the page shipped with and the rest still apply.
 */
function lioness_apply_settings_to_page( $html, $base ) {
	// Keep the displayed price and the invoiced price the same figure.
	$html = preg_replace( '/(\busd:\s*)[0-9]+(?:\.[0-9]+)?/', '${1}' . lioness_price_usd(), $html, 1 );
	$html = preg_replace( '/(\bbhd:\s*)[0-9]+(?:\.[0-9]+)?/', '${1}' . lioness_price_bhd(), $html, 1 );

	// JSON-encoding gives a correctly quoted and escaped JavaScript string.
	$html = preg_replace( '/(\bcourse:\s*)\x27(?:[^\x27\\\\]|\\\\.)*\x27/',
		'${1}' . wp_json_encode( lioness_course_name() ), $html, 1 );

	$note = lioness_course_note();
	if ( '' !== $note ) {
		$html = preg_replace( '/(\bcourseNote:\s*)\x27(?:[^\x27\\\\]|\\\\.)*\x27/',
			'${1}' . wp_json_encode( $note ), $html, 1 );
	}

	$paypal = lioness_paypal_link();
	if ( '' !== $paypal ) {
		$html = str_replace( 'https://www.paypal.com/ncp/payment/2E8SL288SQ56Q', esc_url_raw( $paypal ), $html );
	}

	if ( ! lioness_proof_required() ) {
		$html = preg_replace( '/(\brequired:\s*)true/', '${1}false', $html, 1 );
	}

	// Swapping the artwork for something in the media library.
	$logo = lioness_logo_url();
	if ( '' !== $logo ) {
		$html = str_replace( $base . 'assets/logo-320.png', esc_url_raw( $logo ), $html );
	}
	$qr = lioness_qr_url();
	if ( '' !== $qr ) {
		$html = str_replace( $base . 'assets/benefit-qr.jpg', esc_url_raw( $qr ), $html );
	}

	$html = str_replace( 'hi@prime.aasaad.com', esc_html( lioness_merchant_email() ), $html );

	/*
	 * A marker inside the document rather than a response header. This host
	 * rewrites headers on the way out — X-Lioness never reaches the client —
	 * so which version is live could only be inferred from behaviour. In the
	 * body it survives every cache between here and the browser.
	 */
	$html = str_replace(
		'<meta name="theme-color"',
		'<meta name="generator" content="Lioness Prime ' . esc_attr( LIONESS_VERSION ) . '">' . "\n" . '<meta name="theme-color"',
		$html
	);

	return $html;
}

/* -------------------------------------------------------------------------
 * Enrollment record
 * ---------------------------------------------------------------------- */

add_action( 'init', function () {
	register_post_type( 'lp_enrollment', array(
		'label'           => 'Enrollments',
		'public'          => false,      // never a front-end URL
		'publicly_queryable' => false,
		'exclude_from_search' => true,
		/*
		 * Readable over REST, but only by someone signed in who can edit posts:
		 * WordPress requires that capability for a post type whose `public` is
		 * false, and the filter below refuses anonymous callers outright. This
		 * is what lets enrollments be managed from outside the admin screens;
		 * it is not a way in from the open web.
		 */
		'show_in_rest'    => true,
		'rest_base'       => 'lp_enrollment',
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

/** Enrollment records are never served to a caller who is not signed in. */
add_filter( 'rest_pre_dispatch', function ( $result, $server, $request ) {
	if ( 0 !== strpos( $request->get_route(), '/wp/v2/lp_enrollment' ) ) {
		return $result;
	}
	if ( ! is_user_logged_in() || ! current_user_can( 'edit_posts' ) ) {
		return new WP_Error( 'lioness_forbidden', 'Enrollments are not public.', array( 'status' => 401 ) );
	}
	return $result;
}, 10, 3 );

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
}, 99, 3 );   // after core's own CORS filter, whose header would otherwise win

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
		? '$' . lioness_price_usd()
		: lioness_price_bhd() . ' BHD';

	$proof_file = lioness_store_proof( (string) $request->get_param( 'proof_base64' ), $ref );

	$data = array(
		'name'        => $name,
		'email'       => $email,
		'snapchat'    => $clean( 'snapchat', 60 ),
		'reference'   => $ref,
		'invoice_no'  => 'INV-' . preg_replace( '/^LP-/', '', $ref ),
		'course'      => lioness_course_name(),
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
	update_option( 'lioness_mailing_enrollment', (int) $post_id, false );

	$sent_buyer = wp_mail(
		$email,
		sprintf( 'Your Lioness Prime Course invoice — %s', $data['reference'] ),
		lioness_invoice_html( $data, false ),
		lioness_headers( lioness_merchant_email(), $copies )
	);

	wp_mail(
		lioness_merchant_email(),
		sprintf( 'New enrollment — %s (%s)', $data['name'], $data['reference'] ),
		lioness_invoice_html( $data, true ),
		lioness_headers( $email, lioness_copy_email() ),
		$proof_file ? array( lioness_proof_dir() . '/' . $proof_file ) : array()
	);

	delete_option( 'lioness_mailing_enrollment' );

	return new WP_REST_Response(
		array( 'ok' => (bool) $sent_buyer, 'reference' => $data['reference'] ),
		$sent_buyer ? 200 : 502
	);
}

function lioness_headers( $reply_to, $bcc = '' ) {
	$headers = array(
		'Content-Type: text/html; charset=UTF-8',
		sprintf( 'From: Lioness Prime <%s>', lioness_from_email() ),
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
		lioness_merchant_email(),
		lioness_copy_email(),
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

/* -------------------------------------------------------------------------
 * Updating itself
 *
 * The plugin checks a manifest in its own repository and installs newer
 * versions through WordPress's own upgrader. Because each release carries its
 * version in the core filename, a server caching compiled PHP cannot go on
 * running the old code afterwards.
 *
 * Turn it off under Enrollments → Settings, or with
 * define( 'LIONESS_UPDATE_MANIFEST', '' ) in wp-config.php, and updates become
 * a button in the Plugins screen instead.
 * ---------------------------------------------------------------------- */

function lioness_basename() {
	return plugin_basename( LIONESS_DIR . 'lioness-prime.php' );
}

/** The published manifest, cached so a slow or missing repo cannot slow the site. */
function lioness_manifest( $force = false ) {
	if ( '' === (string) LIONESS_UPDATE_MANIFEST ) {
		return array();
	}
	if ( ! $force ) {
		$cached = get_transient( 'lioness_manifest' );
		if ( is_array( $cached ) ) {
			return $cached;
		}
	}
	$res = wp_remote_get( LIONESS_UPDATE_MANIFEST, array(
		'timeout'    => 10,
		'user-agent' => 'LionessPrime/' . LIONESS_VERSION,
	) );
	if ( is_wp_error( $res ) || 200 !== (int) wp_remote_retrieve_response_code( $res ) ) {
		set_transient( 'lioness_manifest', array(), 15 * MINUTE_IN_SECONDS );   // back off, keep working
		return array();
	}
	$data = json_decode( wp_remote_retrieve_body( $res ), true );
	if ( ! is_array( $data ) || empty( $data['version'] ) || empty( $data['package'] ) ) {
		set_transient( 'lioness_manifest', array(), 15 * MINUTE_IN_SECONDS );
		return array();
	}
	// Only ever accept a package from the same host as the manifest.
	if ( wp_parse_url( $data['package'], PHP_URL_HOST ) !== wp_parse_url( LIONESS_UPDATE_MANIFEST, PHP_URL_HOST ) ) {
		set_transient( 'lioness_manifest', array(), HOUR_IN_SECONDS );
		return array();
	}
	set_transient( 'lioness_manifest', $data, HOUR_IN_SECONDS );
	return $data;
}

function lioness_update_available() {
	$m = lioness_manifest();
	return ( ! empty( $m['version'] ) && version_compare( $m['version'], LIONESS_VERSION, '>' ) ) ? $m : array();
}

add_filter( 'pre_set_site_transient_update_plugins', function ( $transient ) {
	if ( ! is_object( $transient ) ) {
		return $transient;
	}
	$m = lioness_update_available();
	if ( empty( $m ) ) {
		return $transient;
	}
	$transient->response[ lioness_basename() ] = (object) array(
		'slug'         => 'lioness-prime',
		'plugin'       => lioness_basename(),
		'new_version'  => $m['version'],
		'package'      => $m['package'],
		'url'          => isset( $m['url'] ) ? $m['url'] : '',
		'tested'       => isset( $m['tested'] ) ? $m['tested'] : '',
		'requires_php' => isset( $m['requires_php'] ) ? $m['requires_php'] : '7.4',
		'icons'        => array(),
		'banners'      => array(),
	);
	return $transient;
} );

/** Let WordPress install it unattended when that is switched on. */
add_filter( 'auto_update_plugin', function ( $update, $item ) {
	if ( isset( $item->plugin ) && lioness_basename() === $item->plugin ) {
		return lioness_auto_update();
	}
	return $update;
}, 10, 2 );

/**
 * Downloads the package here so its checksum can be checked before WordPress
 * unpacks anything over a working installation.
 */
add_filter( 'upgrader_pre_download', function ( $reply, $package, $upgrader, $hook_extra ) {
	$m = lioness_manifest();
	if ( empty( $m['package'] ) || $package !== $m['package'] ) {
		return $reply;
	}
	if ( ! function_exists( 'download_url' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}
	$file = download_url( $package, 60 );
	if ( is_wp_error( $file ) ) {
		return $file;
	}
	if ( ! empty( $m['sha256'] ) ) {
		$got = hash_file( 'sha256', $file );
		if ( ! hash_equals( strtolower( (string) $m['sha256'] ), strtolower( (string) $got ) ) ) {
			@unlink( $file );
			return new WP_Error( 'lioness_checksum',
				'The downloaded update did not match its published checksum, so it was discarded.' );
		}
	}

	$complete = lioness_package_is_complete( $file );
	if ( is_wp_error( $complete ) ) {
		@unlink( $file );
		return $complete;
	}
	return $file;
}, 10, 4 );

/**
 * Looks inside a downloaded package before anything is unpacked over a working
 * site. A checksum only proves the file arrived intact — it says nothing about
 * whether what was published was any good. An update that installs cleanly and
 * leaves the site serving its theme is worse than one that refuses.
 *
 * @return true|WP_Error
 */
function lioness_package_is_complete( $file ) {
	if ( ! class_exists( 'ZipArchive' ) ) {
		return true;   // cannot look; the checksum will have to do
	}
	$zip = new ZipArchive();
	if ( true !== $zip->open( $file ) ) {
		return new WP_Error( 'lioness_package', 'The update could not be opened as a zip archive.' );
	}

	$needed = array(
		'lioness-prime/lioness-prime.php' => 200,    // the loader, with its plugin header
		'lioness-prime/page/index.html'   => 10000,  // the course page itself
	);
	$missing = array();
	foreach ( $needed as $path => $min_bytes ) {
		$stat = $zip->statName( $path );
		if ( ! $stat || (int) $stat['size'] < $min_bytes ) {
			$missing[] = $path;
		}
	}

	$has_core = false;
	for ( $i = 0; $i < $zip->numFiles; $i++ ) {
		$name = $zip->getNameIndex( $i );
		if ( 0 === strpos( $name, 'lioness-prime/core/lioness-core-' ) && '.php' === substr( $name, -4 ) ) {
			$stat = $zip->statName( $name );
			if ( $stat && (int) $stat['size'] > 5000 ) {
				$has_core = true;
			}
		}
	}
	$zip->close();

	if ( ! $has_core ) {
		$missing[] = 'lioness-prime/core/lioness-core-*.php';
	}
	if ( $missing ) {
		return new WP_Error( 'lioness_package', sprintf(
			'The published update looked incomplete, so it was not installed. Missing or empty: %s. The site is untouched.',
			implode( ', ', $missing )
		) );
	}
	return true;
}

/** Installs a newer version, touching nothing else on the site. */
function lioness_run_update() {
	if ( ! lioness_auto_update() ) {
		return false;
	}
	$m = lioness_manifest( true );
	if ( empty( $m['version'] ) || version_compare( $m['version'], LIONESS_VERSION, '<=' ) ) {
		return false;
	}
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/misc.php';
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

	$basename   = lioness_basename();
	$was_active = is_plugin_active( $basename );

	wp_clean_plugins_cache( true );
	wp_update_plugins();

	$skin     = new Automatic_Upgrader_Skin();
	$upgrader = new Plugin_Upgrader( $skin );
	$result   = $upgrader->upgrade( $basename );
	$ok       = ( true === $result );

	/*
	 * WordPress switches a plugin off before upgrading it and, outside cron,
	 * expects a browser to switch it back on. Nothing here is a browser, so an
	 * update would otherwise leave the site with no course page at all.
	 *
	 * The option is written directly rather than calling activate_plugin(),
	 * which would include the new file on top of the old one already running
	 * in this request and fail on the redeclared functions. The next request
	 * loads the new version cleanly.
	 */
	if ( $was_active ) {
		$active = (array) get_option( 'active_plugins', array() );
		if ( ! in_array( $basename, $active, true ) ) {
			$active[] = $basename;
			sort( $active );
			update_option( 'active_plugins', $active );
		}
	}

	$why = '';
	if ( ! $ok ) {
		if ( is_wp_error( $result ) ) {
			$why = $result->get_error_message();
		} elseif ( method_exists( $skin, 'get_errors' ) && is_wp_error( $skin->get_errors() ) ) {
			$why = $skin->get_errors()->get_error_message();
		}
		if ( '' === $why ) {
			$why = 'The update was declined and nothing was changed.';
		}
	}

	update_option( 'lioness_last_update', array(
		'at'         => time(),
		'from'       => LIONESS_VERSION,
		'to'         => $m['version'],
		'ok'         => $ok,
		'reactivated'=> $was_active,
		'error'      => $why,
	), false );

	return $ok;
}
add_action( 'lioness_cron_update', 'lioness_run_update' );

add_action( 'init', function () {
	if ( ! wp_next_scheduled( 'lioness_cron_update' ) ) {
		wp_schedule_event( time() + 300, 'hourly', 'lioness_cron_update' );
	}
} );

register_deactivation_hook( LIONESS_DIR . 'lioness-prime.php', function () {
	wp_clear_scheduled_hook( 'lioness_cron_update' );
} );

/** Opening the admin also nudges a check, so a fix does not wait on traffic. */
add_action( 'admin_init', function () {
	if ( ! current_user_can( 'update_plugins' ) || get_transient( 'lioness_admin_checked' ) ) {
		return;
	}
	set_transient( 'lioness_admin_checked', 1, 10 * MINUTE_IN_SECONDS );
	if ( lioness_auto_update() && ! empty( lioness_update_available() ) ) {
		lioness_run_update();
	}
} );

/* -------------------------------------------------------------------------
 * Telling someone when something is wrong
 *
 * The page-serving hook fails safe: if it cannot find the page it hands the
 * request to the theme rather than showing an error. That is right for a
 * visitor and wrong for the owner, who sees a site that merely looks like it
 * reverted. Everything that can quietly fail is surfaced here instead.
 * ---------------------------------------------------------------------- */

function lioness_health() {
	$page    = LIONESS_DIR . 'page/index.html';
	$dir     = lioness_proof_dir();
	$manifest = lioness_manifest();

	return array(
		'version'      => LIONESS_VERSION,
		'page_found'   => is_readable( $page ),
		'page_path'    => $page,
		'assets_found' => is_readable( LIONESS_DIR . 'page/assets/logo-320.png' ),
		'proof_writable' => is_dir( $dir ) && is_writable( $dir ),
		'proof_sealed' => is_readable( $dir . '/.htaccess' ),
		'gd'           => function_exists( 'imagecreatefromstring' ),
		'from_email'   => lioness_from_email(),
		'merchant'     => lioness_merchant_email(),
		'price'        => '$' . lioness_price_usd() . ' / ' . lioness_price_bhd() . ' BHD',
		'proof_used'   => size_format( lioness_proof_bytes_used() ),
		'latest'       => isset( $manifest['version'] ) ? $manifest['version'] : '',
		'update_ready' => ! empty( lioness_update_available() ),
		'auto_update'  => lioness_auto_update(),
		'last_update'  => get_option( 'lioness_last_update', array() ),
		'enrollments'  => (int) wp_count_posts( 'lp_enrollment' )->publish,
	);
}

add_action( 'admin_notices', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$h = lioness_health();

	if ( ! $h['page_found'] ) {
		printf(
			'<div class="notice notice-error"><p><strong>Lioness Prime:</strong> the course page file is missing, so the site is showing your theme instead of the course. Looked for <code>%s</code>. Re-upload the plugin.</p></div>',
			esc_html( $h['page_path'] )
		);
	} elseif ( ! $h['assets_found'] ) {
		echo '<div class="notice notice-warning"><p><strong>Lioness Prime:</strong> the course page is being served but its images are missing. Re-upload the plugin.</p></div>';
	}

	if ( ! $h['proof_writable'] ) {
		echo '<div class="notice notice-error"><p><strong>Lioness Prime:</strong> payment screenshots cannot be saved — the uploads folder is not writable. Enrollments will still be recorded and emailed.</p></div>';
	}

	$last = $h['last_update'];
	if ( ! empty( $last['ok'] ) === false && ! empty( $last['error'] ) ) {
		printf(
			'<div class="notice notice-warning is-dismissible"><p><strong>Lioness Prime:</strong> an automatic update to %s did not complete — %s. Updating from the Plugins screen will work.</p></div>',
			esc_html( isset( $last['to'] ) ? $last['to'] : '' ),
			esc_html( $last['error'] )
		);
	}
} );

/** Flags an enrollment whose email did not leave the building. */
add_action( 'wp_mail_failed', function ( $error ) {
	$id = (int) get_option( 'lioness_mailing_enrollment', 0 );
	if ( $id && is_wp_error( $error ) ) {
		update_post_meta( $id, 'lp_mail_error', mb_substr( $error->get_error_message(), 0, 300 ) );
	}
} );

add_filter( 'manage_lp_enrollment_posts_columns', function ( $cols ) {
	$cols['lp_mail'] = 'Email';
	return $cols;
}, 20 );

add_action( 'manage_lp_enrollment_posts_custom_column', function ( $col, $post_id ) {
	if ( 'lp_mail' !== $col ) {
		return;
	}
	$err = (string) get_post_meta( $post_id, 'lp_mail_error', true );
	if ( '' === $err ) {
		echo '<span style="color:#1D6B3F">sent</span>';
	} else {
		echo '<span style="color:#B03A4E" title="' . esc_attr( $err ) . '">failed</span>';
	}
}, 20, 2 );

/* -------------------------------------------------------------------------
 * Settings screen
 * ---------------------------------------------------------------------- */

add_action( 'admin_menu', function () {
	add_submenu_page(
		'edit.php?post_type=lp_enrollment',
		'Lioness Prime settings',
		'Settings',
		'manage_options',
		'lioness-settings',
		'lioness_settings_page'
	);
} );

add_action( 'admin_init', function () {
	register_setting( 'lioness_settings_group', 'lioness_settings', array(
		'sanitize_callback' => 'lioness_sanitize_settings',
		'default'           => array(),
	) );
} );

function lioness_sanitize_settings( $input ) {
	$out = array();
	$in  = is_array( $input ) ? $input : array();

	// A price must look like a number, or it is ignored and the old one stands.
	foreach ( array( 'price_usd', 'price_bhd' ) as $k ) {
		$v = isset( $in[ $k ] ) ? trim( (string) $in[ $k ] ) : '';
		$out[ $k ] = ( '' === $v || preg_match( '/^[0-9]+(\.[0-9]{1,3})?$/', $v ) ) ? $v : lioness_opt( $k, '' );
	}
	foreach ( array( 'merchant_email', 'copy_email', 'from_email' ) as $k ) {
		$v = isset( $in[ $k ] ) ? sanitize_email( trim( (string) $in[ $k ] ) ) : '';
		$out[ $k ] = ( '' === $v || is_email( $v ) ) ? $v : lioness_opt( $k, '' );
	}
	$out['course_name'] = isset( $in['course_name'] ) ? mb_substr( sanitize_text_field( $in['course_name'] ), 0, 160 ) : '';
	$out['course_note'] = isset( $in['course_note'] ) ? mb_substr( sanitize_text_field( $in['course_note'] ), 0, 300 ) : '';
	foreach ( array( 'paypal_link', 'logo_url', 'qr_url' ) as $k ) {
		$out[ $k ] = isset( $in[ $k ] ) ? esc_url_raw( trim( (string) $in[ $k ] ) ) : '';
	}
	$out['proof_required'] = empty( $in['proof_required'] ) ? '0' : '1';
	$out['auto_update']    = empty( $in['auto_update'] ) ? '0' : '1';

	delete_transient( 'lioness_manifest' );
	return $out;
}

function lioness_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$h = lioness_health();
	$yes = '<span style="color:#1D6B3F">yes</span>';
	$no  = '<span style="color:#B03A4E;font-weight:600">no</span>';
	?>
	<div class="wrap">
		<h1>Lioness Prime</h1>

		<h2 class="title">Status</h2>
		<table class="widefat striped" style="max-width:820px">
			<tbody>
			<tr><td style="width:240px">Running version</td><td><code><?php echo esc_html( $h['version'] ); ?></code></td></tr>
			<tr><td>Course page found</td><td><?php echo $h['page_found'] ? $yes : $no . ' — <code>' . esc_html( $h['page_path'] ) . '</code>'; ?></td></tr>
			<tr><td>Page images found</td><td><?php echo $h['assets_found'] ? $yes : $no; ?></td></tr>
			<tr><td>Screenshots can be saved</td><td><?php echo $h['proof_writable'] ? $yes : $no; ?></td></tr>
			<tr><td>Screenshot folder sealed off</td><td><?php echo $h['proof_sealed'] ? $yes : $no; ?></td></tr>
			<tr><td>Image library (GD) available</td><td><?php echo $h['gd'] ? $yes : $no . ' — uploads are still checked, just not redrawn'; ?></td></tr>
			<tr><td>Price charged</td><td><?php echo esc_html( $h['price'] ); ?></td></tr>
			<tr><td>Invoices sent from</td><td><?php echo esc_html( $h['from_email'] ); ?></td></tr>
			<tr><td>Enrollments recorded</td><td><?php echo (int) $h['enrollments']; ?></td></tr>
			<tr><td>Screenshots stored</td><td><?php echo esc_html( $h['proof_used'] ); ?></td></tr>
			<tr><td>Latest published version</td><td>
				<?php
				echo $h['latest'] ? esc_html( $h['latest'] ) : '<em>could not reach the update server</em>';
				echo $h['update_ready'] ? ' — <strong>an update is available</strong>' : ( $h['latest'] ? ' — up to date' : '' );
				?>
			</td></tr>
			</tbody>
		</table>

		<form method="post" action="options.php" style="margin-top:28px">
			<?php settings_fields( 'lioness_settings_group' ); ?>
			<h2 class="title">Settings</h2>
			<p class="description" style="max-width:640px">Leave a field empty to keep the built-in value. A price that is not a plain number is ignored rather than saved, so the page can never end up showing nothing.</p>
			<table class="form-table" role="presentation">
				<tr><th scope="row"><label for="lp_usd">PayPal price (USD)</label></th>
					<td><input name="lioness_settings[price_usd]" id="lp_usd" type="text" class="regular-text"
						value="<?php echo esc_attr( lioness_opt( 'price_usd', '' ) ); ?>" placeholder="<?php echo esc_attr( LIONESS_PRICE_USD ); ?>"></td></tr>
				<tr><th scope="row"><label for="lp_bhd">Benefit Pay price (BHD)</label></th>
					<td><input name="lioness_settings[price_bhd]" id="lp_bhd" type="text" class="regular-text"
						value="<?php echo esc_attr( lioness_opt( 'price_bhd', '' ) ); ?>" placeholder="<?php echo esc_attr( LIONESS_PRICE_BHD ); ?>"></td></tr>
				<tr><th scope="row"><label for="lp_cn">Course name</label></th>
					<td><input name="lioness_settings[course_name]" id="lp_cn" type="text" class="large-text"
						value="<?php echo esc_attr( lioness_opt( 'course_name', '' ) ); ?>" placeholder="<?php echo esc_attr( LIONESS_COURSE_NAME ); ?>"></td></tr>
				<tr><th scope="row"><label for="lp_note">Course description</label></th>
					<td><textarea name="lioness_settings[course_note]" id="lp_note" rows="3" class="large-text"><?php echo esc_textarea( lioness_opt( 'course_note', '' ) ); ?></textarea>
					<p class="description">Shown on the page and on every invoice.</p></td></tr>
				<tr><th scope="row"><label for="lp_pp">PayPal payment link</label></th>
					<td><input name="lioness_settings[paypal_link]" id="lp_pp" type="url" class="large-text"
						value="<?php echo esc_attr( lioness_opt( 'paypal_link', '' ) ); ?>" placeholder="https://www.paypal.com/ncp/payment/…"></td></tr>
				<tr><th scope="row"><label for="lp_me">Enrollments go to</label></th>
					<td><input name="lioness_settings[merchant_email]" id="lp_me" type="email" class="regular-text"
						value="<?php echo esc_attr( lioness_opt( 'merchant_email', '' ) ); ?>" placeholder="<?php echo esc_attr( LIONESS_MERCHANT_EMAIL ); ?>"></td></tr>
				<tr><th scope="row"><label for="lp_ce">Blind copy to</label></th>
					<td><input name="lioness_settings[copy_email]" id="lp_ce" type="email" class="regular-text"
						value="<?php echo esc_attr( lioness_opt( 'copy_email', '' ) ); ?>" placeholder="<?php echo esc_attr( LIONESS_INVOICE_COPY ); ?>">
					<p class="description">Never visible to the buyer.</p></td></tr>
				<tr><th scope="row"><label for="lp_fe">Send invoices from</label></th>
					<td><input name="lioness_settings[from_email]" id="lp_fe" type="email" class="regular-text"
						value="<?php echo esc_attr( lioness_opt( 'from_email', '' ) ); ?>" placeholder="<?php echo esc_attr( LIONESS_FROM_EMAIL ); ?>">
					<p class="description">Must be an address this site is allowed to send as.</p></td></tr>
				<tr><th scope="row"><label for="lp_logo">Logo image URL</label></th>
					<td><input name="lioness_settings[logo_url]" id="lp_logo" type="url" class="large-text"
						value="<?php echo esc_attr( lioness_opt( 'logo_url', '' ) ); ?>" placeholder="leave empty to use the bundled logo"></td></tr>
				<tr><th scope="row"><label for="lp_qr">Benefit QR image URL</label></th>
					<td><input name="lioness_settings[qr_url]" id="lp_qr" type="url" class="large-text"
						value="<?php echo esc_attr( lioness_opt( 'qr_url', '' ) ); ?>" placeholder="leave empty to use the bundled QR"></td></tr>
				<tr><th scope="row">Proof of payment</th>
					<td><label><input type="checkbox" name="lioness_settings[proof_required]" value="1" <?php checked( lioness_proof_required() ); ?>>
						Require a screenshot before an invoice is issued</label></td></tr>
				<tr><th scope="row">Updates</th>
					<td><label><input type="checkbox" name="lioness_settings[auto_update]" value="1" <?php checked( lioness_auto_update() ); ?>>
						Install new versions automatically</label>
					<p class="description">When off, updates appear in the Plugins screen for you to approve.</p></td></tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
