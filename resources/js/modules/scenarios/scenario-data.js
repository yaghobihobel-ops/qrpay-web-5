export const scenarioData = {
    qr: {
        defaultLocale: 'zh',
        locales: {
            zh: {
                label: '中文 (简体)',
                direction: 'ltr',
                summary: '适用于线下扫码和动态二维码的标准流程。',
                steps: [
                    '客户使用支持的应用（如支付宝或微信）扫描二维码收款码。',
                    '终端校验金额与币种，生成支付摘要并提示用户确认。',
                    'QRPay 返回交易令牌并更新界面状态，提示付款结果。',
                ],
                compliance: {
                    title: '监管提示',
                    copy: '遵循中国网联条码支付互联互通规范与人民银行风控要求。',
                },
                handoff: '{"type":"qr","ttl":120,"region":"CN"}',
            },
            fa: {
                label: 'فارسی',
                direction: 'rtl',
                summary: 'روند استاندارد پذیرش QR برای کسب‌وکارهای ایرانی.',
                steps: [
                    'مشتری کد QR را با اپلیکیشن بانکی یا کیف پول موبایلی اسکن می‌کند.',
                    'مبلغ و نرخ تبدیل در ترمینال بررسی شده و درخواست تأیید به درگاه ارسال می‌شود.',
                    'QRPay توکن تراکنش را صادر کرده و نتیجه پرداخت به صورت آنی نمایش داده می‌شود.',
                ],
                compliance: {
                    title: 'نکات مقرراتی',
                    copy: 'مطابق ضوابط شاپرک و الزامات بانک مرکزی برای تراکنش‌های مبتنی بر QR.',
                },
                handoff: '{"type":"qr","ttl":120,"region":"IR"}',
            },
            ru: {
                label: 'Русский',
                direction: 'ltr',
                summary: 'Последовательность приёма QR-платежей для мерчантов в РФ.',
                steps: [
                    'Плательщик сканирует QR-код через приложение банка или Mir Pay.',
                    'Терминал сверяет сумму, валюту и назначение платежа, затем запрашивает подтверждение.',
                    'QRPay получает токен операции и фиксирует статус в журнале расчётов.',
                ],
                compliance: {
                    title: 'Регуляторные требования',
                    copy: 'Соответствие стандартам Банка России и НСПК по динамическим QR-кодам.',
                },
                handoff: '{"type":"qr","ttl":180,"region":"RU"}',
            },
        },
    },
    alipay: {
        defaultLocale: 'zh',
        locales: {
            zh: {
                label: '中文 (简体)',
                direction: 'ltr',
                summary: '面向移动端的支付宝快捷支付授权流程。',
                steps: [
                    '客户端调用支付宝 SDK 并传入签名后的 orderStr。',
                    '用户在支付宝内完成指纹或人脸识别并确认扣款。',
                    '支付宝回调 QRPay notify_url，返回 trade_status=SUCCESS 及签名。',
                ],
                compliance: {
                    title: '监管提示',
                    copy: '遵循支付宝跨境数字支付指引及人民银行网络支付标准。',
                },
                handoff: '{"channel":"alipay","authType":"APP","timeout":180}',
            },
            fa: {
                label: 'فارسی',
                direction: 'rtl',
                summary: 'مراحل تکمیل پرداخت از طریق کیف پول Alipay برای کاربران فارسی‌زبان.',
                steps: [
                    'فرآیند با ارسال orderStr امضا شده به SDK علی‌پی آغاز می‌شود.',
                    'کاربر در اپلیکیشن علی‌پی احراز هویت بیومتریک یا رمز عبور را تأیید می‌کند.',
                    'نتیجه پرداخت به notify_url بازگردانده شده و QRPay توکن نهایی را ذخیره می‌کند.',
                ],
                compliance: {
                    title: 'نکات مقرراتی',
                    copy: 'مطابق محدودیت‌های ارزی ایران و دستورالعمل تراکنش‌های برون‌مرزی علی‌پی.',
                },
                handoff: '{"channel":"alipay","authType":"APP","timeout":180,"locale":"fa"}',
            },
            ru: {
                label: 'Русский',
                direction: 'ltr',
                summary: 'Интеграция Alipay для мобильных кошельков в регионе СНГ.',
                steps: [
                    'Приложение инициирует SDK Alipay и передаёт подписанные параметры заказа.',
                    'Пользователь подтверждает оплату через биометрию или PIN внутри приложения Alipay.',
                    'Alipay отправляет уведомление на notify_url, после чего QRPay подтверждает транзакцию.',
                ],
                compliance: {
                    title: 'Регуляторные требования',
                    copy: 'Соблюдение правил Alipay и норм валютного контроля ЕАЭС.',
                },
                handoff: '{"channel":"alipay","authType":"APP","timeout":180,"region":"RU"}',
            },
        },
    },
    bankAuth: {
        defaultLocale: 'ru',
        locales: {
            zh: {
                label: '中文 (简体)',
                direction: 'ltr',
                summary: 'BluBank/Yoomonea 银行账户授权流程，支持实时余额校验。',
                steps: [
                    '用户跳转至 BluBank/Yoomonea 授权页并选择开户银行。',
                    '完成短信或生物识别验证后授权共享账户与交易明细。',
                    'QRPay 使用访问令牌检索账户并生成额度或风险评估。',
                ],
                compliance: {
                    title: '监管提示',
                    copy: '符合当地开放银行规范及反洗钱 (AML/KYC) 要求。',
                },
                handoff: '{"provider":"BluBank","method":"oauth2","expires_in":600}',
            },
            fa: {
                label: 'فارسی',
                direction: 'rtl',
                summary: 'مراحل احراز هویت بانکی BluBank/Yoomonea برای کاربران فارسی‌زبان.',
                steps: [
                    'کاربر به صفحه احراز هویت BluBank/Yoomonea هدایت و بانک مقصد را انتخاب می‌کند.',
                    'ارسال کد یکبارمصرف یا تأیید بیومتریک برای صدور توکن دسترسی.',
                    'QRPay با استفاده از توکن، حساب را بررسی کرده و وضعیت تطبیق را ثبت می‌کند.',
                ],
                compliance: {
                    title: 'نکات مقرراتی',
                    copy: 'مطابق الزامات احراز هویت مشتری و دستورالعمل‌های ضد پولشویی بانک مرکزی ایران.',
                },
                handoff: '{"provider":"Yoomonea","method":"oauth2","expires_in":600,"region":"IR"}',
            },
            ru: {
                label: 'Русский',
                direction: 'ltr',
                summary: 'Пошаговая проверка банковского счёта через BluBank/Yoomonea.',
                steps: [
                    'Пользователь переходит на страницу BluBank/Yoomonea и выбирает банк.',
                    'Проходит SMS/Push-подтверждение или биометрическую проверку для выдачи токена.',
                    'QRPay использует токен для получения реквизитов и обновляет статус KYC.',
                ],
                compliance: {
                    title: 'Регуляторные требования',
                    copy: 'Соответствует нормам открытого банкинга и требованиям Росфинмониторинга.',
                },
                handoff: '{"provider":"BluBank","method":"oauth2","expires_in":600,"region":"RU"}',
            },
        },
    },
};

export function getScenarioConfig(key) {
    return scenarioData[key];
}
