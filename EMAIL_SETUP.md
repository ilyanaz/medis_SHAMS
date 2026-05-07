# Email Setup For Medis SHAMS

This project can send email to real inboxes such as Gmail, Outlook, Hotmail, Microsoft 365, and Yahoo Mail.

## What You Mean In Practice

You want two things:

1. Each login account should eventually have a real email address.
2. The system should send notifications to that real email inbox.

That is the correct direction.

## Important Difference

A real email address does not get created by Laravel.

Laravel only sends messages.
The actual mailbox must already exist on a real provider, for example:

- Gmail
- Outlook / Hotmail / Microsoft 365
- Yahoo Mail

So later, when a user registers or an admin creates an account, you store the user's real email address in the system and send notifications to that address.

## Recommended SMTP Providers For Malaysia Users

### Gmail

```env
MAIL_MAILER=smtp
MAIL_SCHEME=tls
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-real-email@gmail.com
MAIL_PASSWORD="your-google-app-password"
MAIL_FROM_ADDRESS=your-real-email@gmail.com
MAIL_FROM_NAME="Medis SHAMS"
```

Notes:
- Turn on Google 2-Step Verification.
- Create an App Password in your Google account.
- Do not use your normal Gmail password.

### Outlook / Hotmail / Microsoft 365

```env
MAIL_MAILER=smtp
MAIL_SCHEME=tls
MAIL_HOST=smtp.office365.com
MAIL_PORT=587
MAIL_USERNAME=your-real-email@outlook.com
MAIL_PASSWORD="your-password-or-app-password"
MAIL_FROM_ADDRESS=your-real-email@outlook.com
MAIL_FROM_NAME="Medis SHAMS"
```

Notes:
- Some accounts require SMTP AUTH to be enabled.
- Some business accounts require an app password or admin approval.

### Yahoo Mail

```env
MAIL_MAILER=smtp
MAIL_SCHEME=tls
MAIL_HOST=smtp.mail.yahoo.com
MAIL_PORT=587
MAIL_USERNAME=your-real-email@yahoo.com
MAIL_PASSWORD="your-yahoo-app-password"
MAIL_FROM_ADDRESS=your-real-email@yahoo.com
MAIL_FROM_NAME="Medis SHAMS"
```

Notes:
- Use a Yahoo app password.
- Do not use the main Yahoo password directly.

## What Was Added To This Project

The app now includes:

- An Email Setup page in the sidebar
- A test email form
- A real Laravel Mailable for testing SMTP delivery

Open this page:

`/email`

If Herd is working, use:

`http://medisshams-app.test/email`

## How To Test

1. Open `.env`
2. Replace the mail settings with your real provider credentials
3. Run:
   `php artisan optimize:clear`
4. Open the Email Setup page
5. Send a test email to your real inbox

## Best Practice For Your Future Login System

When we build the real authentication module, each login user should have:

- username or email for login
- hashed password
- real email address
- optional email verification status
- notification preferences

For your current custom `users` table, we should later add an `email` column if you want login accounts to receive notifications directly.

## Recommended Next Step

After you choose the provider you want to use, I can do the next part for you:

1. add `email` to the login users table
2. make login accounts use real email addresses
3. add password reset email flow
4. add notification emails for account creation and alerts
