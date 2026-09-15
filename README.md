# Lioness Prime Course — landing & enrollment page

A single, self-contained landing page for the **Lioness Prime Course** subscription.
No build step, no backend, no dependencies — open `index.html` and it works.

```
index.html                              the whole page (markup, styles, logic)
build-plugin.sh                         builds the installable WordPress plugin
assets/logo.png                         the Lioness Prime mark, full resolution
assets/logo-320.png                     the same mark, web-sized (used on the page)
assets/favicon.png                      browser tab icon
assets/benefit-qr.jpg                   the Benefit Pay QR code
wordpress/lioness-prime/                WordPress plugin: serves the page + mails invoices
dist/lioness-prime.zip                  the built plugin (run ./build-plugin.sh)
```

To change the logo later, replace `assets/logo.png` and `assets/logo-320.png`
with your new artwork at the same sizes.

## What it does

1. **Details** — visitor fills in name, email and Snapchat username.
2. **Payment** — they choose **PayPal** or **Benefit Pay**. Both open in a panel
   on the same page; nobody is sent to a different page.
   A reference number (`LP-YYMM-XXXX`) is generated and shown on both options,
   so the buyer and Lioness Prime quote the same code.
3. **Proof of payment** — confirming the payment requires attaching a screenshot
   of it. The image is scaled down in the browser, shown back to the buyer, and
   travels with the invoice.
4. **Invoice** — a printable invoice for the course appears immediately, with the
   reference, the details, the amount, the payment method and the screenshot. It
   can be printed, saved as PDF, copied, or sent to you in one tap.

Entries are kept in the visitor's own browser (`localStorage`) so a refresh
doesn't lose their reference number. Nothing is transmitted anywhere until they
tap "Send my details".

## Set it up

Everything configurable lives in one `CONFIG` block near the bottom of
`index.html` (search for `LIONESS PRIME — configuration`).

| Setting | What to put there |
| --- | --- |
| `price.usd` / `price.bhd` | `150.00` USD through PayPal, `50` BHD through Benefit Pay |
| `paypal.link` | Your PayPal payment link (already set) |
| `benefit.qrImage` | Path to your Benefit Pay QR (`assets/benefit-qr.jpg`) |
| `benefit.qrData` | Fallback: your Benefit Pay link; a QR is generated from it if no image exists |
| `benefit.accountName` / `accountNumber` / `iban` | Optional, shown under the QR for manual transfers |
| `email.provider` / `endpoint` | `wordpress` + the site's invoice endpoint — see *Emailing the invoice* |
| `proof.required` | `true` — a payment screenshot must be attached before the invoice |
| `proof.maxMB` / `maxDimension` | Largest file accepted, and the size it is scaled to |
| `merchant.email` | Used by the manual "Send my details" button |
| `merchant.whatsapp` | Digits only, e.g. `97333000000` — takes priority over email |
| `merchant.snapchat` | Shown on the invoice as a contact |

If neither a WhatsApp number nor an email is set, "Send my details" copies the
enrollment text to the clipboard instead.

## Putting it live on prime.aasaad.com

The page ships as a small WordPress plugin that does two jobs: it serves the
enrollment page, and it emails the invoices. One upload covers both.

1. Run `./build-plugin.sh` to produce **`dist/lioness-prime.zip`** (it is rebuilt
   from `index.html`, `assets/` and the plugin PHP, so run it again after any edit).
2. In WP Admin go to **Plugins → Add New Plugin → Upload Plugin**, choose the zip,
   **Install Now**, then **Activate**.
3. Open **https://prime.aasaad.com** — the course page is there. It is also served
   at **/course**.

The theme is bypassed entirely, so the page looks exactly as it does locally: no
site header, no footer, no theme styles. `wp-admin` and everything else on the site
are untouched. To stop the plugin taking over the front page and keep only
`/course`, add `define( 'LIONESS_TAKE_FRONT_PAGE', false );` to `wp-config.php`.

To update the page later: edit `index.html`, run `./build-plugin.sh`, and upload the
new zip over the old plugin (WordPress asks you to confirm replacing it).

## Emailing the invoice

When a buyer confirms their payment, two emails go out:

| Email | To | Also receives it | Contents |
| --- | --- | --- | --- |
| The invoice | the buyer | `hi@prime.aasaad.com` and the Gmail, as Bcc | Invoice no., date, reference, item, amount, method, transaction no., and their payment screenshot |
| New enrollment | `hi@prime.aasaad.com` | the Gmail, as Bcc | The same details plus the buyer's name, email and Snapchat, with the screenshot attached as a file |

So both of your addresses receive a copy of the buyer's own invoice **and** the
full payment details. Both copies are Bcc, so a buyer never sees either address
on their invoice.

Mail goes out through the site itself, which already runs WP Mail SMTP, so it
arrives from your own address. No third-party mail service, no monthly sending
limit, no signup, no `noreply@` address.

The buyer's payment screenshot is attached to the copy that reaches you, and shown
inside both emails.

Every enrollment is also recorded under **Enrollments** in the WP Admin menu, with
the buyer's details, reference, method, amount and a thumbnail of their payment
screenshot — so you have a list to check payments against even if an email goes
astray.

**Uploads are checked on the server.** The screenshot is identified by decoding the
bytes themselves rather than trusting what the browser claims, anything that is not
a real JPEG, PNG or WebP is discarded, the file is capped at
`LIONESS_MAX_PROOF_MB`, and it is stored under a generated filename.

**Test the mail before launching.** Go to **WP Mail SMTP → Tools → Email Test** and
send yourself one. If it does not arrive, mail is not leaving the server and the
invoices will not either; configuring WP Mail SMTP fixes that, and nothing about
this page needs to change.

**Changing the addresses.** `LIONESS_MERCHANT_EMAIL` at the top of the plugin sets
who gets the copy (comma-separate for more than one). `LIONESS_FROM_EMAIL` on the
line below sets the address invoices are sent from. Both are
`hi@prime.aasaad.com`.

## Security

The enrollment endpoint is open to the internet, because the page has no login.
Everything below assumes a hostile caller.

**It cannot be used to send mail to anyone else.** Recipients are always the
buyer's own address plus a fixed list set in this file. The message body is
built on the server, never taken from the request. Every field is sanitised,
and the subject cannot carry a line break, so headers cannot be injected.

**The browser is not trusted with the price.** `LIONESS_PRICE_USD`,
`LIONESS_PRICE_BHD` and `LIONESS_COURSE_NAME` decide what the invoice says, and
the amount the page displays is rewritten from those same constants as it is
served — so editing the page in a browser before submitting changes nothing, and
the two figures cannot drift apart. The payment method is matched against a list
of two; anything else is treated as Benefit Pay.

**Payment screenshots are not in the media library.** `/wp-json/wp/v2/media`
lets anyone list every attachment on a WordPress site, and these are bank
receipts. They are written to `wp-content/uploads/lioness-proofs/`, which carries
an `.htaccess` denying the web server, under a filename with 24 random
characters. They are served only through `admin-ajax.php` to a signed-in user who
can edit that enrollment, with a nonce. Deleting an enrollment deletes its file.

**Uploads are identified by their content.** The bytes are decoded to confirm
they are really a JPEG, PNG or WebP — what the browser claimed is ignored — and
then the image is redrawn from its decoded pixels. A file that is both a valid
image and a valid script does not survive being redrawn. SVG is refused
outright, files over `LIONESS_MAX_PROOF_MB` are refused, and so is anything
larger than 6000px on a side.

**Flooding is capped** at 5 invoices per visitor per hour and 40 across the whole
site per hour, with a honeypot field that bots fill in and people never see.

**Cross-origin requests** are answered only for this site's own origin. Add
others to `LIONESS_ALLOWED_ORIGINS` if you ever serve the page from elsewhere.

**The page is served with** a content security policy, `X-Frame-Options`
(so it cannot be framed inside another site to harvest details),
`X-Content-Type-Options`, a referrer policy and a permissions policy.

**Site-wide, the plugin also** closes `/wp-json/wp/v2/users` and
`/wp-json/wp/v2/media` to strangers — the users endpoint was publishing your
admin login name — redirects `?author=` probes, turns off XML-RPC, hides the
WordPress version, and stops the login form saying whether it was the username
or the password that was wrong. Each of these has a constant at the top of the
plugin if you need it back; `LIONESS_DISABLE_XMLRPC` is the one to watch if you
ever use the WordPress mobile app.

### Standing up to abuse

**Length caps everywhere.** Name 120 characters, email 150, Snapchat 30,
transaction number 60 — enforced by `maxlength` in the browser, again in the
page's own validation, and again on the server, where anything longer is cut
rather than stored. A 50,000-character name arrives as 120.

**Oversized requests never reach the parser.** A POST to the enrollment route
declaring more than `LIONESS_MAX_REQUEST_KB` is refused on the earliest hook a
plugin can use, so a multi-megabyte body costs a header check instead of a JSON
decode.

**Flood limits at four depths:** no two enrollments from one visitor within
`LIONESS_MIN_GAP` seconds, `LIONESS_RATE_LIMIT` an hour each, ten malformed
requests an hour before that address is ignored, and `LIONESS_GLOBAL_LIMIT`
across the whole site per hour whatever the source.

**Screenshots cannot fill the disk.** Each is capped at
`LIONESS_MAX_PROOF_MB`, scaled down in the browser before upload, and once the
stored total passes `LIONESS_PROOF_QUOTA_MB` new images are refused while the
enrollment itself is still recorded.

**Nothing submits twice.** The enrollment form, the confirm button and the
resend button all refuse to fire while one is already in flight, and resending
an invoice has a fifteen-second cool-down.

**Logins are throttled.** `LIONESS_LOGIN_TRIES` failures from one address and it
is locked out for `LIONESS_LOGIN_LOCKOUT` seconds — WordPress on its own will
let someone guess passwords as fast as they can send requests.

**The page is cheap to serve.** It carries an ETag and a five-minute cache
lifetime, so repeat visits cost a 304 and WP Super Cache can serve most hits as
a flat file without PHP running at all. Because a cached file cannot carry
headers, the content security policy travels in the document itself, and the
page refuses to run inside another site's frame rather than relying on
`X-Frame-Options`.

**Cross-origin.** WordPress core answers every origin on REST routes, so this
plugin sets the header itself on its own route: an unknown caller is handed this
site's origin, which its browser will refuse to match.

### What this does not cover

Keep WordPress, plugins and the theme updated, use a long unique admin password
with two-factor if DreamHost offers it, and take backups. No amount of care in
this plugin helps if the admin account is guessed.

If the plugin is not installed, the page simply skips the email and falls back to
the print / copy / send buttons. Setting `email.provider` to `'emailjs'` switches
to EmailJS instead if you ever move the page off this host.

### Adding the Benefit Pay QR

The supplied QR (`assets/benefit-qr.jpg`, 1024 x 1024) is used as-is and is never
redrawn or re-encoded.

**About it not scanning.** The file is cropped flush to the edge of the code, with
no white border. A QR needs a *quiet zone* — a clear margin of at least four
modules on all four sides — or readers will not lock onto it. The page therefore
displays it inside a white frame that supplies that margin. If you use the image
anywhere else (print, a story, a poster), add the same white border around it or it
will be unreliable there too.

**Test it before going live:** open the payment panel on a phone and scan the code
from the screen with BenefitPay. If it still struggles, a version of the code in
pure black rather than warm grey will read more easily in poor light.

To swap the QR later, replace the file, or put the link the QR contains into
`benefit.qrData` and the page will draw the code itself as vector.

## Hosting it somewhere else

The page is plain static HTML, so any host works — Netlify, Vercel, GitHub Pages,
Cloudflare Pages, shared hosting. Upload `index.html` and `assets/` keeping them in
the same relative positions. The invoice email still works from another host: it
posts to the endpoint on prime.aasaad.com, which answers cross-origin requests.

## Notes

- Card details are never entered on this page — PayPal and the BenefitPay app
  handle them.
- Payment confirmation is manual: the visitor tells the page they've paid, and the
  invoice is marked *pending confirmation* until you verify the reference on your
  PayPal / Benefit statement. The emailed invoice says the same, so nobody reads it
  as proof that the money arrived.
- Fonts load from Google Fonts and degrade to system serif/sans if unavailable.
