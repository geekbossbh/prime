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

When a buyer confirms their payment the invoice is emailed automatically — to
them, copied to **hi@prime.aasaad.com** — carrying the reference number, the
amount, the payment method and the course description.

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

**Safety.** The endpoint is public, because the page has no login. It cannot be
used to send mail to anyone else: recipients are always the buyer's own address
plus the fixed copy list, the message body is built on the server rather than
taken from the request, every field is sanitised, and one visitor may trigger at
most six invoices an hour.

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
