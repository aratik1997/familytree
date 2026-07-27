<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('Claim your account') }}</title>
</head>
<body style="margin:0; padding:0; background-color:#FBF8F1; font-family: Georgia, 'Times New Roman', serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#FBF8F1; padding:40px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="480" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border:1px solid #E4DCC8; border-radius:8px; overflow:hidden;">
                    <tr>
                        <td style="background-color:#0B3D2E; padding:24px 32px; text-align:center;">
                            <span style="color:#C9A227; font-size:20px; letter-spacing:1px;">THE KHANDANI LEGACY</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px; color:#2A2438; font-size:15px; line-height:1.6;">
                            <p>{{ __('Dear') }} {{ $person->full_name }},</p>
                            <p>
                                {{ __('You have turned 18, and your family tree profile on The Khandani Legacy is ready for you to claim. Once you set a password, you will manage your own profile from now on.') }}
                            </p>
                            <table role="presentation" cellpadding="0" cellspacing="0" style="margin:24px 0;">
                                <tr>
                                    <td style="background-color:#1F7A4D; border-radius:6px;">
                                        <a href="{{ $claimUrl }}" style="display:inline-block; padding:12px 28px; color:#ffffff; text-decoration:none; font-size:14px; font-weight:bold;">
                                            {{ __('Claim Your Account') }}
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            <p style="font-size:13px; color:#6b6470;">
                                {{ __('This link expires in 7 days. If the button above does not work, copy and paste this URL into your browser:') }}
                                <br>
                                <a href="{{ $claimUrl }}" style="color:#1F7A4D;">{{ $claimUrl }}</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
