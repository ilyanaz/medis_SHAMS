<?php

declare(strict_types=1);

$pageTitle = 'Safety & Health Assessment Management System';
$csrfToken = csrf_token();
$emailError = isset($errors) ? $errors->first('email') : '';
$emailValue = old('email', '');
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-white">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">

    <title><?php echo $pageTitle; ?></title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    <style>
        *,
        ::before,
        ::after {
            box-sizing: border-box;
            border-width: 0;
            border-style: solid;
            border-color: #e5e7eb;
            --tw-border-spacing-x: 0;
            --tw-border-spacing-y: 0;
            --tw-translate-x: 0;
            --tw-translate-y: 0;
            --tw-rotate: 0;
            --tw-skew-x: 0;
            --tw-skew-y: 0;
            --tw-scale-x: 1;
            --tw-scale-y: 1;
            --tw-ring-inset: ;
            --tw-ring-offset-width: 0px;
            --tw-ring-offset-color: #fff;
            --tw-ring-color: rgb(59 130 246 / .5);
            --tw-ring-offset-shadow: 0 0 #0000;
            --tw-ring-shadow: 0 0 #0000;
            --tw-shadow: 0 0 #0000;
            --tw-shadow-colored: 0 0 #0000;
        }

        ::before,
        ::after {
            --tw-content: "";
        }

        ::backdrop {
            --tw-border-spacing-x: 0;
            --tw-border-spacing-y: 0;
            --tw-translate-x: 0;
            --tw-translate-y: 0;
            --tw-rotate: 0;
            --tw-skew-x: 0;
            --tw-skew-y: 0;
            --tw-scale-x: 1;
            --tw-scale-y: 1;
            --tw-ring-inset: ;
            --tw-ring-offset-width: 0px;
            --tw-ring-offset-color: #fff;
            --tw-ring-color: rgb(59 130 246 / .5);
            --tw-ring-offset-shadow: 0 0 #0000;
            --tw-ring-shadow: 0 0 #0000;
            --tw-shadow: 0 0 #0000;
            --tw-shadow-colored: 0 0 #0000;
        }

        html {
            line-height: 1.5;
            -webkit-text-size-adjust: 100%;
            -moz-tab-size: 4;
            -o-tab-size: 4;
            tab-size: 4;
            font-family: Figtree, ui-sans-serif, system-ui, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
            font-feature-settings: normal;
            font-variation-settings: normal;
            -webkit-tap-highlight-color: transparent;
            height: 100%;
            background-color: rgb(255 255 255 / 1);
        }

        body {
            margin: 0;
            line-height: inherit;
            min-height: 100%;
            font-family: Figtree, ui-sans-serif, system-ui, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            color: rgb(17 24 39 / 1);
            background: rgb(243 244 246 / 1);
        }

        input,
        button {
            font: inherit;
        }

        .page {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .brand-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 24px;
            text-decoration: none;
        }

        .brand-logo {
            height: 96px;
            width: auto;
            display: block;
        }

        .login-card {
            width: 100%;
            max-width: 448px;
            margin-top: 4px;
            padding: 24px;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .login-title {
            display: block;
            margin: 0 0 18px;
            color: rgb(17, 24, 39);
            text-align: center;
            font-size: 1rem;
            font-weight: 500;
            line-height: 1.5rem;
        }

        .error-message {
            margin: 0 0 12px;
            color: rgb(185 28 28 / 1);
            font-size: 0.95rem;
            text-align: center;
        }

        .field + .field {
            margin-top: 16px;
        }

        .field label {
            display: block;
            color: rgb(17, 24, 39);
            font-size: 1rem;
            font-weight: 500;
            line-height: 1.5rem;
            margin-bottom: 4px;
        }

        .field input {
            display: block;
            width: 100%;
            margin-top: 4px;
            border-radius: 0.375rem;
            padding: 0.375rem 0.75rem;
            color: rgb(17, 24, 39);
            background: rgb(255, 255, 255);
            box-shadow: inset 0 0 0 1px rgb(209 213 219 / 1);
            min-height: 42px;
            outline: none;
        }

        .field input:focus {
            box-shadow: inset 0 0 0 2px rgb(3 105 161 / 1);
        }

        .actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            margin-top: 16px;
        }

        .login-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.375rem;
            background: rgb(21 128 61 / 1);
            padding: 0.5rem 0.75rem;
            color: #ffffff;
            font-size: 1rem;
            font-weight: 600;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.3s ease;
        }

        .login-button:hover {
            background: rgb(22 163 74 / 1);
        }

        .login-button:active {
            transform: translateY(1px);
        }

        @media (max-width: 640px) {
            .page {
                padding: 20px 16px;
            }

            .brand-logo {
                height: 78px;
            }

            .login-card {
                max-width: 100%;
                padding: 20px;
            }

            .actions {
                justify-content: stretch;
            }

            .login-button {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="page">
        <div>
            <a class="brand-link" href="/">
                <img src="/images/logos/medis-logo-left-right.png" class="brand-logo" alt="Medis SHAMS logo">
            </a>
        </div>

        <div class="login-card">
            <label class="login-title" for="email">Developer Login Page</label>

            <?php if ($emailError !== ''): ?>
                <p class="error-message"><?php echo htmlspecialchars($emailError, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>

            <form method="POST" action="<?php echo route('developer.login.attempt'); ?>">
                <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off">

                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email" value="<?php echo htmlspecialchars($emailValue, ENT_QUOTES, 'UTF-8'); ?>" required autocomplete="username" autofocus>
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <input id="password" type="password" name="password" required autocomplete="current-password">
                </div>

                <div class="actions">
                    <button type="submit" class="login-button">Log in</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
