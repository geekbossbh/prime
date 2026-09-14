# Lioness Prime Course — landing & enrollment page

A single, self-contained landing page for the **Lioness Prime Course** subscription.
No build step, no backend, no dependencies — open `index.html` and it works.

```
index.html          the whole page (markup, styles, logic)
assets/logo.svg     the gold Lioness Prime mark
assets/favicon.svg  browser tab icon
assets/benefit-qr.png   ← YOU ADD THIS (your Benefit Pay QR code)
```

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
| `price.bhd` / `price.usd` | **⚠️ Currently placeholders** — set your real prices |
| `paypal.link` | Your PayPal payment link (already set) |
| `benefit.qrImage` | Path to your Benefit Pay QR — save it as `assets/benefit-qr.png` |
| `benefit.qrData` | Fallback: your Benefit Pay link; a QR is generated from it if no image exists |
| `benefit.accountName` / `accountNumber` / `iban` | Optional, shown under the QR for manual transfers |
| `merchant.email` | Enables the "Send my details" email button |
| `merchant.whatsapp` | Digits only, e.g. `97333000000` — takes priority over email |
| `merchant.snapchat` | Shown on the invoice as a contact |

If neither a WhatsApp number nor an email is set, "Send my details" copies the
enrollment text to the clipboard instead.

### Adding the Benefit Pay QR

Save your QR as `assets/benefit-qr.png` — use a clean, high-resolution export
(600 px or larger, square, no drop shadow) so it stays sharp and scannable.
If that file is missing and `benefit.qrData` is empty, the payment panel shows a
"QR code not set up yet" note instead of a broken image.

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
  PayPal / Benefit statement.
- Fonts load from Google Fonts and degrade to system serif/sans if unavailable.
