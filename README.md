# Lioness Prime Course — landing & enrollment page

A single, self-contained landing page for the **Lioness Prime Course** subscription.
No build step, no backend, no dependencies — open `index.html` and it works.

```
index.html                              the whole page (markup, styles, logic)
assets/logo.png                         the Lioness Prime mark, full resolution
assets/logo-320.png                     the same mark, web-sized (used on the page)
assets/favicon.png                      browser tab icon
assets/benefit-qr.jpg                   the Benefit Pay QR code
wordpress/lioness-invoice-mailer.php    drop-in plugin that emails the invoice
```

To change the logo later, replace `assets/logo.png` and `assets/logo-320.png`
with your new artwork at the same sizes.

## What it does

1. **Details** — visitor fills in name, email, Snapchat username and phone.
2. **Payment** — they choose **PayPal** or **Benefit Pay**. Both open in a panel
   on the same page; nobody is sent to a different page.
   A reference number (`LP-YYMM-XXXX`) is generated and shown on both options,
   so the buyer and Lioness Prime quote the same code.
3. **Invoice** — a printable invoice for the course appears immediately, with the
   reference, the details, the amount and the payment method. It can be printed,
   saved as PDF, copied, or sent to you in one tap.

Entries are kept in the visitor's own browser (`localStorage`) so a refresh
doesn't lose their reference number. Nothing is transmitted anywhere until they
tap "Send my details".

## Set it up

Everything configurable lives in one `CONFIG` block near the bottom of
`index.html` (search for `LIONESS PRIME — configuration`).

| Setting | What to put there |
| --- | --- |
| `price.usd` / `price.bhd` | `150.00` USD, and `56.400` BHD (150 USD at Bahrain's 0.376 peg) |
| `paypal.link` | Your PayPal payment link (already set) |
| `benefit.qrImage` | Path to your Benefit Pay QR (`assets/benefit-qr.jpg`) |
| `benefit.qrData` | Fallback: your Benefit Pay link; a QR is generated from it if no image exists |
| `benefit.accountName` / `accountNumber` / `iban` | Optional, shown under the QR for manual transfers |
| `email.provider` / `endpoint` | `wordpress` + the site's invoice endpoint — see *Emailing the invoice* |
| `merchant.email` | Used by the manual "Send my details" button |
| `merchant.whatsapp` | Digits only, e.g. `97333000000` — takes priority over email |
| `merchant.snapchat` | Shown on the invoice as a contact |

If neither a WhatsApp number nor an email is set, "Send my details" copies the
enrollment text to the clipboard instead.

## Emailing the invoice

When a buyer confirms their payment the invoice is emailed automatically — to
them, copied to you — carrying the reference number, the amount, the payment
method and the course description.

It sends through **prime.aasaad.com itself**. The site already runs WP Mail SMTP,
so the mail goes out from your own address. No third-party mail service, no
monthly sending limit, no signup, and no `noreply@` address.

### Installing the mailer (about two minutes)

1. Zip `wordpress/lioness-invoice-mailer.php` into `lioness-invoice-mailer.zip`.
2. In WP Admin go to **Plugins → Add New Plugin → Upload Plugin**, choose the zip,
   install and activate it.
   (Or copy the `.php` file straight into `wp-content/plugins/` over SFTP and
   activate it from the Plugins screen.)
3. That's it — the page is already pointed at the endpoint the plugin creates,
   `https://prime.aasaad.com/wp-json/lioness/v1/invoice`.

Every enrollment is also recorded under **Enrollments** in the WP Admin menu, with
the buyer's details, reference, method and amount — so you have a list to check
payments against even if an email goes astray.

**Who gets the copy.** Edit the `LIONESS_MERCHANT_EMAIL` line at the top of the
plugin; it is currently `hi@prime.aasaad.com, angel.lionness@gmail.com`. The
address the invoice is *sent from* is `LIONESS_FROM_EMAIL` on the line below.

**Safety.** The endpoint is public, because the page has no login. It cannot be
used to send mail to anyone else: recipients are always the buyer's own address
plus the fixed copy list, the message body is built on the server rather than
taken from the request, every field is sanitised, and one visitor may trigger at
most six invoices an hour.

If the plugin is not installed, the page simply skips the email and falls back to
the print / copy / send buttons, so nothing breaks in the meantime. Setting
`email.provider` to `'emailjs'` switches to EmailJS instead if you ever move the
page off this host.

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

## Publish it

Any static host works — Netlify, Vercel, GitHub Pages, Cloudflare Pages, or plain
shared hosting. Upload `index.html` and the `assets/` folder, keeping them in the
same relative positions.

For WordPress, the page can be dropped in as a full-width custom template or via a
plugin that allows raw HTML pages; keep `assets/` reachable at the same relative path.

## Notes

- Card details are never entered on this page — PayPal and the BenefitPay app
  handle them.
- Payment confirmation is manual: the visitor tells the page they've paid, and the
  invoice is marked *pending confirmation* until you verify the reference on your
  PayPal / Benefit statement. The emailed invoice says the same, so nobody reads it
  as proof that the money arrived.
- Fonts load from Google Fonts and degrade to system serif/sans if unavailable.
