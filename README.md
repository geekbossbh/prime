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
| `price.usd` / `price.bhd` | `150.00` USD, and `56.400` BHD (150 USD at Bahrain's 0.376 peg) |
| `paypal.link` | Your PayPal payment link (already set) |
| `benefit.qrImage` | Path to your Benefit Pay QR — save it as `assets/benefit-qr.png` |
| `benefit.qrData` | Fallback: your Benefit Pay link; a QR is generated from it if no image exists |
| `benefit.accountName` / `accountNumber` / `iban` | Optional, shown under the QR for manual transfers |
| `email.publicKey` / `serviceId` / `templateId` | EmailJS keys — see *Emailing the invoice* below |
| `email.merchantCopy` | Address copied on every invoice (`angel.lionness@gmail.com`) |
| `merchant.email` | Used by the manual "Send my details" button |
| `merchant.whatsapp` | Digits only, e.g. `97333000000` — takes priority over email |
| `merchant.snapchat` | Shown on the invoice as a contact |

If neither a WhatsApp number nor an email is set, "Send my details" copies the
enrollment text to the clipboard instead.

## Emailing the invoice

When a buyer confirms their payment, the invoice is emailed automatically — to
them, with a copy to `angel.lionness@gmail.com` — carrying the reference number.

**You do not need a mail server, a domain, or a `noreply@` address.** The page
sends through your own Gmail using EmailJS, which is free for 200 emails a month.
Until the three keys below are filled in the page simply skips the email and
falls back to the print / copy / send buttons, so nothing breaks in the meantime.

### One-time setup (about five minutes, free)

1. Sign up at **emailjs.com** with `angel.lionness@gmail.com`.
2. **Email Services → Add New Service → Gmail**, connect that same Gmail account.
   Copy the **Service ID**.
3. **Email Templates → Create New Template**. Set the fields to:
   - **To:** `{{to_email}}`
   - **Cc:** `{{cc_email}}`
   - **Reply-To:** `{{reply_to}}`
   - **Subject:** `Your Lioness Prime Course invoice — {{reference}}`
   - **Content:** write the email and drop the variables in where you want them:
     `{{first_name}}`, `{{customer_name}}`, `{{invoice_no}}`, `{{invoice_date}}`,
     `{{reference}}`, `{{course}}`, `{{amount}}`, `{{method}}`, `{{transaction}}`,
     `{{email}}`, `{{snapchat}}`, `{{phone}}`, and `{{invoice_text}}` for the whole
     invoice as plain text.

   Copy the **Template ID**.
4. **Account → General**, copy the **Public Key**.
5. Paste all three into the `email` block in `index.html`.

Emails arrive from your real Gmail address, so replies go straight back to you.

### Adding the Benefit Pay QR

Two ways, best first:

1. **Give the page the link your QR contains** (`benefit.qrData`). Point your
   phone camera at your own Benefit QR — it will show a link or a block of text.
   Copy that exactly into `benefit.qrData` and the page draws the QR itself, as
   vector, sharp at any size on any screen.
2. **Or save the QR image** as `assets/benefit-qr.png` — a clean, high-resolution
   export (600 px or larger, square, no drop shadow, plenty of white around it).

If neither is set, the payment panel shows a "QR code not set up yet" note rather
than a broken image.

**If a QR won't scan**, it is almost always the *quiet zone* — a QR needs a clear
white margin of at least four modules on every side. Cropping tight to the black
edge, or putting the code on a coloured or patterned background, stops readers
recognising it. The generated QR leaves the correct margin; if you supply your own
image, keep its white border intact.

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
