const localeBlueprint = {
    zh: {
        label: '中文 (简体)',
        direction: 'ltr',
        labels: {
            date: '日期格式',
            amount: '金额格式',
            reference: '参考字段',
        },
    },
    fa: {
        label: 'فارسی',
        direction: 'rtl',
        labels: {
            date: 'قالب تاریخ',
            amount: 'قالب مبلغ',
            reference: 'مرجع',
        },
    },
    ru: {
        label: 'Русский',
        direction: 'ltr',
        labels: {
            date: 'Формат даты',
            amount: 'Формат суммы',
            reference: 'Реквизит',
        },
    },
};

const contexts = {
    'add-money': {
        defaultLocale: 'zh',
        locales: {
            zh: {
                summary: '为中国用户优化的充值表单指引。',
                formats: {
                    date: 'YYYY/MM/DD（例如 2025/03/18）',
                    amount: '￥1,234.50',
                    reference: '18 位身份证或统一社会信用代码',
                },
                instructions: [
                    '金额字段自动包含人民币符号，请仅填写数字。',
                    '日期请按照 YYYY/MM/DD 或 YYYY年MM月DD日 填写，避免混用数字与汉字。',
                    '在备注中填写付款用途并提供证件号后四位，以满足央行监测要求。',
                ],
                regulation: '遵循《非银行支付机构网络支付业务管理办法》。',
            },
            fa: {
                summary: 'راهنمای تکمیل فرم برای کاربران ایران.',
                formats: {
                    date: 'YYYY/MM/DD یا ۱۴۰۴/۱۲/۲۸',
                    amount: '۱٬۲۳۴٫۵۰ ﷼',
                    reference: 'شناسه شبا یا کد ملی ۱۰ رقمی',
                },
                instructions: [
                    'مبالغ را با جداکننده هزارگان «٬» و اعشار «٫» وارد کنید.',
                    'تاریخ را مطابق تقویم شمسی یا میلادی ثبت کرده و واحد پول را مشخص نمایید.',
                    'در توضیحات، شناسه شبا یا کد پیگیری ساتنا/پایا را اضافه کنید تا از تعلیق جلوگیری شود.',
                ],
                regulation: 'مطابق دستورالعمل‌های بانک مرکزی ایران برای پرداخت‌های ریالی.',
            },
            ru: {
                summary: 'Рекомендации для пользователей из России.',
                formats: {
                    date: 'ДД.ММ.ГГГГ (пример: 18.03.2025)',
                    amount: '1 234,50 ₽',
                    reference: 'ИНН или номер счёта (20 цифр)',
                },
                instructions: [
                    'Используйте пробел как разделитель тысяч и запятую для копеек.',
                    'Дата указывается в формате ДД.ММ.ГГГГ в соответствии с ГОСТ Р 7.0.97.',
                    'В поле примечания добавьте назначение платежа и ИНН/БИК получателя.',
                ],
                regulation: 'Соблюдайте требования 161-ФЗ и положений Банка России.',
            },
        },
    },
    'money-out': {
        defaultLocale: 'ru',
        locales: {
            zh: {
                summary: '提现到本地账户的合规指南。',
                formats: {
                    date: 'YYYY-MM-DD 或 YYYY/MM/DD',
                    amount: '¥1,234.50',
                    reference: 'CN-对账编号 / 银行卡后四位',
                },
                instructions: [
                    '仅填写数字金额，系统会自动显示本地币种。',
                    '提现到境内银行请提供开户姓名的中文全称及 Swift/CNAPS 代码。',
                    '备注中补充资金用途（如“跨境结算”）以便顺利通关。',
                ],
                regulation: '根据国家外汇管理局要求，必要时需上传贸易凭证。',
            },
            fa: {
                summary: 'راهنمای تسویه برای کاربران فارسی‌زبان.',
                formats: {
                    date: 'YYYY/MM/DD یا ۱۴۰۴/۱۲/۲۸',
                    amount: '۱٬۲۳۴٬۵۰۰ ریال',
                    reference: 'کد شبا ۲۴ رقمی یا کد پیگیری ساتنا',
                },
                instructions: [
                    'مبلغ را بر اساس واحد مقصد (ریال/تومان) و سقف روزانه مشخص کنید.',
                    'نام دارنده حساب باید دقیقاً مطابق مدارک شناسایی باشد.',
                    'در توضیحات، نوع تسویه (ساتنا/پایا) و مقصد وجوه را وارد نمایید.',
                ],
                regulation: 'پرداخت‌های بالای سقف نیازمند تایید صرافی مجاز بانک مرکزی است.',
            },
            ru: {
                summary: 'Инструкции по выводу средств на российские счета.',
                formats: {
                    date: 'ДД.ММ.ГГГГ',
                    amount: '1 234,50 ₽',
                    reference: 'БИК + номер счёта / УИН',
                },
                instructions: [
                    'Сумму указывайте в рублях с пробелом как разделителем тысяч.',
                    'ФИО получателя должно совпадать с банковскими реквизитами.',
                    'В назначении платежа укажите код операции и ИНН получателя.',
                ],
                regulation: 'Выплаты проходят валютный контроль в соответствии с 161-ФЗ.',
            },
        },
    },
};

contexts.default = contexts['add-money'];

export function getLocalesForContext(context) {
    const contextConfig = contexts[context] ?? contexts.default;
    const { locales } = contextConfig;

    return Object.keys(locales).map((code) => {
        const base = localeBlueprint[code] ?? { label: code, direction: 'ltr', labels: {} };
        const config = locales[code];

        const formats = {
            date: config.formats?.date ?? 'YYYY-MM-DD',
            amount: config.formats?.amount ?? '1,000.00',
            reference: config.formats?.reference ?? '',
        };

        return {
            code,
            label: base.label,
            direction: base.direction,
            summary: config.summary,
            formats,
            instructions: config.instructions ?? [],
            labels: {
                date: base.labels?.date ?? 'Date format',
                amount: base.labels?.amount ?? 'Amount format',
                reference: base.labels?.reference ?? 'Reference',
                ...(config.labels ?? {}),
            },
            regulation: config.regulation ?? '',
        };
    });
}

export function getDefaultLocaleForContext(context) {
    const contextConfig = contexts[context] ?? contexts.default;
    return contextConfig.defaultLocale ?? 'en';
}
