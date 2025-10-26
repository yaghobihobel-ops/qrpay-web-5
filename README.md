<<<<<<<< Update Guide >>>>>>>>>>>

Immediate Older Version: 4.9.0
Current Version: 5.0.0

1. Google reCAPTCHA integration added.
2. Resolved Mobile top-up issues.
3. Added the Hindi language.
4. Updated Google 2FA For User & Merchant App.
5. Added PayStack Webhook URL.
6. Added Live Exchange Currency Rate API(Currency Layer).

Please Use This Commands On Your Terminal To Update Full System
1. To Run project Please Run This Command On Your Terminal
    composer update && composer dumpautoload && php artisan migrate:fresh --seed && php artisan passport:install --force

## Branch synchronization

All recent pull request changes have been merged into the `main` branch so it now mirrors the reviewed updates from the feature workflows. This ensures the default branch contains the latest application improvements without requiring additional manual steps.
