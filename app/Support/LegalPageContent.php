<?php

namespace App\Support;

final class LegalPageContent
{
    /** @return array<int, array{title: string, slug: string, content: string}> */
    public static function pages(): array
    {
        return [
            [
                'title' => 'Privacy Policy',
                'slug' => 'privacy-policy',
                'content' => self::privacyPolicy(),
            ],
            [
                'title' => 'Terms of Use',
                'slug' => 'terms-of-use',
                'content' => self::termsOfUse(),
            ],
        ];
    }

    private static function privacyPolicy(): string
    {
        $appName = config('app.name', 'TNF Today');
        $contactEmail = 'contact@tnftoday.com';
        $siteUrl = rtrim((string) config('app.url'), '/');

        return <<<HTML
<p><strong>Last updated:</strong> July 2026</p>

<p>{$appName} ("we", "us", or "our") operates the website and mobile application at <a href="{$siteUrl}">{$siteUrl}</a>. This Privacy Policy explains how we collect, use, store, and protect your information when you use our news, video, and ePaper services.</p>

<h2>1. Information we collect</h2>
<ul>
<li><strong>Account information:</strong> When you register or sign in, we collect your name, email address, and password (stored in encrypted form).</li>
<li><strong>Member submissions:</strong> If you are a registered member who submits news, we store the content, images, and metadata you provide.</li>
<li><strong>Usage data:</strong> We may collect basic technical information such as browser or app type, device information, pages viewed, and approximate access times to operate and improve the service.</li>
<li><strong>Push notifications:</strong> If you enable notifications, a device token may be processed through our push notification provider (OneSignal) to deliver alerts you opt into.</li>
</ul>

<h2>2. How we use your information</h2>
<ul>
<li>To create and manage your account</li>
<li>To provide news, videos, ePaper, and related features</li>
<li>To review and publish member submissions where applicable</li>
<li>To send service-related communications and, where permitted, notifications</li>
<li>To maintain security, prevent abuse, and comply with law</li>
</ul>

<h2>3. Legal basis</h2>
<p>We process personal data where necessary to perform our contract with you (providing the service), with your consent (where required), or for our legitimate interests in operating a secure news platform.</p>

<h2>4. Sharing of information</h2>
<p>We do not sell your personal information. We may share data only with:</p>
<ul>
<li>Hosting and infrastructure providers that help us run the website and app</li>
<li>Email and notification service providers</li>
<li>Authorities when required by applicable law</li>
</ul>

<h2>5. Data retention</h2>
<p>We retain account information while your account is active. If you delete your account, we remove or anonymize personal data within a reasonable period, except where retention is required by law or for legitimate security purposes.</p>

<h2>6. Your rights and account deletion</h2>
<p>You may update your profile information from your account settings. You may delete your account at any time:</p>
<ol>
<li>Sign in to {$appName}</li>
<li>Go to <strong>My Account</strong></li>
<li>Open <strong>Profile &amp; delete account</strong></li>
<li>Scroll to <strong>Delete Account</strong> and confirm with your password</li>
</ol>
<p>Direct link (requires sign-in): <a href="{$siteUrl}/profile">{$siteUrl}/profile</a></p>
<p>For help with account deletion or data requests, email <a href="mailto:{$contactEmail}">{$contactEmail}</a>.</p>

<h2>7. Security</h2>
<p>We use HTTPS encryption in transit and industry-standard safeguards to protect account data. No method of transmission or storage is completely secure, and we encourage you to use a strong, unique password.</p>

<h2>8. Children</h2>
<p>Our service is not directed to children under 13, and we do not knowingly collect personal information from children under 13.</p>

<h2>9. Changes to this policy</h2>
<p>We may update this Privacy Policy from time to time. The updated version will be posted on this page with a revised "Last updated" date.</p>

<h2>10. Contact us</h2>
<p>If you have questions about this Privacy Policy, contact us at <a href="mailto:{$contactEmail}">{$contactEmail}</a>.</p>
HTML;
    }

    private static function termsOfUse(): string
    {
        $appName = config('app.name', 'TNF Today');
        $contactEmail = 'contact@tnftoday.com';
        $siteUrl = rtrim((string) config('app.url'), '/');

        return <<<HTML
<p><strong>Last updated:</strong> July 2026</p>

<p>These Terms of Use govern your access to and use of {$appName}, including our website, mobile application, news articles, videos, and digital ePaper editions at <a href="{$siteUrl}">{$siteUrl}</a>. By using our services, you agree to these terms.</p>

<h2>1. Use of the service</h2>
<p>You may use {$appName} for personal, non-commercial reading and viewing unless we give you written permission for another use. You agree not to misuse the service, attempt unauthorized access, scrape content at scale, or interfere with normal operation.</p>

<h2>2. Accounts</h2>
<p>Some features require an account. You are responsible for keeping your login credentials secure and for activity under your account. You must provide accurate registration information.</p>

<h2>3. Member submissions</h2>
<p>If you submit news or other content as a member, you represent that you have the right to submit it. We may review, edit, publish, reject, or remove submissions at our discretion. Published content may be displayed publicly on {$appName}.</p>

<h2>4. Intellectual property</h2>
<p>News articles, videos, ePaper layouts, logos, branding, and other materials on {$appName} are owned by us or our licensors and are protected by applicable copyright and trademark laws. You may not copy, redistribute, or republish our content without permission except as allowed by law or explicit sharing features in the app.</p>

<h2>5. ePaper and premium content</h2>
<p>Some ePaper editions or features may require an active subscription or membership. Access rules are shown in the app. We may change availability, pricing, or subscription terms with reasonable notice where required.</p>

<h2>6. Third-party links and embeds</h2>
<p>Our service may include links to third-party websites or embedded media. We are not responsible for third-party content, policies, or practices.</p>

<h2>7. Disclaimer</h2>
<p>Content is provided for general information. While we strive for accuracy, we do not guarantee that all content is complete, current, or error-free. Use of the service is at your own risk.</p>

<h2>8. Limitation of liability</h2>
<p>To the fullest extent permitted by law, {$appName} and its operators are not liable for indirect, incidental, special, or consequential damages arising from your use of the service.</p>

<h2>9. Account termination and deletion</h2>
<p>You may delete your account at any time from <a href="{$siteUrl}/profile">Profile &amp; delete account</a>. We may suspend or terminate accounts that violate these terms or applicable law.</p>

<h2>10. Changes</h2>
<p>We may update these Terms of Use from time to time. Continued use after changes are posted constitutes acceptance of the updated terms.</p>

<h2>11. Governing law</h2>
<p>These terms are governed by the laws of India, without regard to conflict-of-law principles, except where mandatory local consumer protections apply.</p>

<h2>12. Contact</h2>
<p>Questions about these terms: <a href="mailto:{$contactEmail}">{$contactEmail}</a>.</p>
HTML;
    }
}
