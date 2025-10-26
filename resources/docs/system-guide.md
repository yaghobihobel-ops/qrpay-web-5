# راهنمای سامانه QRPay

> آخرین به‌روزرسانی: ۱۴۰۳/۰۷/۰۵

## فهرست مطالب
- [نمای کلی سامانه](#overview)
- [مسیرهای توسعه و لایه‌ها](#development-roadmap)
- [خلاصه ماژول‌ها](#modules)
  - [پرداخت](#module-payment)
  - [اکسچنج](#module-exchange)
  - [برداشت](#module-withdrawal)
  - [احراز هویت (KYC)](#module-kyc)
  - [کارت و کیف پول مجازی](#module-card)
- [راهنماهای نسخه‌بندی شده](#versioned-guides)
- [پیوست‌ها و لینک‌های کمکی](#appendix)

<a id="overview"></a>
## نمای کلی سامانه

QRPay از معماری لایه‌ای لاراول ۹ بهره می‌برد. هسته‌ی دامنه در دایرکتوری‌های `app/` و `resources/` نگهداری می‌شود و تعامل با سرویس‌ها از طریق کنترلرهای تحت فضای نام `App\Http\Controllers` انجام می‌شود. فایل‌های پیکربندی در `config/` و مهاجرت‌ها در `database/` در دسترس هستند.

<a id="development-roadmap"></a>
## مسیرهای توسعه و لایه‌ها

1. **تعریف قراردادها:** ابتدا قراردادهای سرویس و DTO ها در `app/Services` یا `app/Contracts` (در صورت نیاز به ایجاد) تعریف می‌شوند. رفرنس: [تعریف قراردادها](guides/contracts.md).
2. **لایه سرویس:** منطق تجاری در سرویس‌ها پیاده‌سازی شده و از طریق کنترلرها فراخوانی می‌شود. ساختار پیشنهادی در [ساختار سرویس‌ها](guides/service-architecture.md) آمده است.
3. **ارائه در کنترلر:** کنترلرها در `app/Http/Controllers/Admin` و `app/Http/Controllers/User` قرار دارند و پاسخ را به ویوها ارسال می‌کنند.
4. **نمایش در ویو:** قالب‌ها در `resources/views` نگهداری می‌شوند. برای پنل ادمین از `resources/views/admin` استفاده کنید.
5. **پایش و مانیتورینگ:** برای اطمینان از صحت فرایندها، سناریوهای پایش در [پایش](guides/monitoring.md) مستند شده است.

<a id="modules"></a>
## خلاصه ماژول‌ها

ماژول‌های کلیدی سامانه در جدول زیر خلاصه شده‌اند:

| ماژول | مسیر اصلی کد | نقاط توسعه | کمک‌های درون‌سیستمی |
| --- | --- | --- | --- |
| پرداخت | `app/Http/Controllers/Admin/MakePaymentController.php`<br>`resources/views/admin/sections/make-payment/` | افزودن روش‌های پرداخت جدید در `routes/admin.php` و گسترش کلاس‌های سرویس مرتبط در `app/Services/Payment` | منوی «Make Payment» در پنل ادمین (`admin.make.payment.index`) |
| اکسچنج | `app/Http/Controllers/Admin/ExchangeRateController.php`<br>`resources/views/admin/sections/currency/exchange-rate/` | به‌روزرسانی سرویس‌های نرخ ارز در `app/Services/Exchange` و ثبت API‌های جدید در `routes/admin.php` | بخش «Exchange Rate» (`admin.exchange.rate.index`) |
| برداشت | `app/Http/Controllers/Admin/MoneyOutController.php`<br>`resources/views/admin/sections/money-out/` | افزودن سیاست‌های برداشت در `app/Policies` و گسترش اکشن‌ها در `MoneyOutController` | منوی «Money Out» (`admin.money.out.index`) |
| احراز هویت (KYC) | `app/Http/Controllers/Admin/SetupKycController.php`<br>`resources/views/admin/sections/setup/kyc/` | تعریف فرم‌های جدید در `resources/views/admin/sections/setup/kyc/fields` و نگهداری اعتبارسنجی در `SetupKycController` | صفحه «Setup KYC» (`admin.setup.kyc.index`) |
| کارت و کیف پول مجازی | `app/Http/Controllers/Admin/VirtualCardController.php`<br>`resources/views/admin/sections/virtual-card/` | ایجاد درگاه‌های جدید در `app/Services/Card` و تنظیم مسیرها در `routes/admin.php` | بخش «Virtual Card» (`admin.virtual.card.api`) |

<a id="module-payment"></a>
### پرداخت
- **توضیح کوتاه:** مدیریت تراکنش‌های درون سیستمی و درگاه‌های پرداخت.
- **گام‌های توسعه:**
  1. تعریف قرارداد سرویس پرداخت جدید در `app/Services/Payment`.
  2. ثبت در کنترلر `MakePaymentController` و route معادل.
  3. طراحی ویو در `resources/views/admin/sections/make-payment/`.
- **لینک کمک داخلی:** منوی «Make Payment» در پنل ادمین با route `admin.make.payment.index`.

<a id="module-exchange"></a>
### اکسچنج
- **توضیح کوتاه:** مدیریت نرخ‌های تبدیل و همگام‌سازی با API‌های خارجی.
- **گام‌های توسعه:**
  1. افزودن ارائه‌دهنده جدید در سرویس‌های `app/Services/Exchange`.
  2. تنظیم مجوزها و route در `routes/admin.php`.
  3. به‌روزرسانی ویوهای `resources/views/admin/sections/currency/exchange-rate/`.
- **لینک کمک داخلی:** صفحه «Exchange Rate» در پنل (`admin.exchange.rate.index`).

<a id="module-withdrawal"></a>
### برداشت
- **توضیح کوتاه:** مدیریت درخواست‌های برداشت و صف تسویه.
- **گام‌های توسعه:**
  1. تعریف سیاست‌های جدید در `app/Policies` یا middleware مرتبط.
  2. توسعه اکشن‌های `MoneyOutController`.
  3. بروزرسانی جدول‌های راهبری در `resources/views/admin/sections/money-out/`.
- **لینک کمک داخلی:** منوی «Money Out» (`admin.money.out.index`).

<a id="module-kyc"></a>
### احراز هویت (KYC)
- **توضیح کوتاه:** مدیریت فرم‌ها و وضعیت احراز هویت کاربران.
- **گام‌های توسعه:**
  1. افزودن فیلد در `resources/views/admin/sections/setup/kyc/fields`.
  2. افزودن قوانین اعتبارسنجی در `SetupKycController` و فرم درخواست.
  3. نگهداری سناریوهای پایش در [پایش](guides/monitoring.md#نسخه-۱۰).
- **لینک کمک داخلی:** صفحه «Setup KYC» (`admin.setup.kyc.index`).

<a id="module-card"></a>
### کارت و کیف پول مجازی
- **توضیح کوتاه:** مدیریت کارت‌های مجازی و تنظیمات API.
- **گام‌های توسعه:**
  1. پیاده‌سازی اتصال در `app/Services/Card`.
  2. اعمال تغییرات در `VirtualCardController` و مسیرهای `routes/admin.php`.
  3. به‌روزرسانی قالب‌های `resources/views/admin/sections/virtual-card/`.
- **لینک کمک داخلی:** صفحه «Virtual Card API» (`admin.virtual.card.api`).

<a id="versioned-guides"></a>
## راهنماهای نسخه‌بندی شده

<a id="نسخه-۱۱"></a>
<a id="نسخه-۱۲"></a>
<a id="نسخه-۱۰"></a>

- [تعریف قراردادها v1.1](guides/contracts.md#نسخه-۱۱)
- [ساختار سرویس‌ها v1.2](guides/service-architecture.md#نسخه-۱۲)
- [پایش v1.0](guides/monitoring.md#نسخه-۱۰)

<a id="appendix"></a>
## پیوست‌ها و لینک‌های کمکی

- **مسیرهای خط فرمان:**
  - `php artisan docs:build` برای اعتبارسنجی لینک‌ها و وجود فایل‌های راهنما.
  - `php artisan config:cache` پس از تغییر در پیکربندی.
- **مستندات خارجی:**
  - [Laravel 9.x Docs](https://laravel.com/docs/9.x)
  - [League CommonMark](https://commonmark.thephpleague.com/)
- **مسیرهای فایل مهم:**
  - استقرار استایل‌ها: `resources/sass/`
  - اسکریپت‌های پنل: `resources/js/admin/`
  - الگوهای ایمیل: `resources/views/mail-templates/`
