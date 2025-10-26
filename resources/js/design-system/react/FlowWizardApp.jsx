import React, { useMemo, useState } from 'react';
import StepProgress from './components/StepProgress.jsx';
import InlineValidation from './components/InlineValidation.jsx';
import { buildValidator, required, numeric, minValue, maxValue, email as emailRule } from '../utils/validation.js';
import { formatCurrency } from '../tokens.js';

const FlowWizardApp = ({
    flow,
    title,
    subtitle,
    formId,
    csrfToken,
    steps = [],
    currency,
    locale,
    startingValues = {},
    meta = {},
}) => {
    const [currentStep, setCurrentStep] = useState(0);
    const [formState, setFormState] = useState({ ...startingValues });
    const [errors, setErrors] = useState({});
    const [isSubmitting, setIsSubmitting] = useState(false);

    const validators = useMemo(() => {
        const ruleBuilders = {};
        steps.forEach((step) => {
            step.fields.forEach((field) => {
                const rules = [];
                if (field.required) rules.push(required);
                if (field.type === 'number') rules.push(numeric);
                if (field.rules?.min !== undefined) rules.push(minValue(field.rules.min));
                if (field.rules?.max !== undefined) rules.push(maxValue(field.rules.max));
                if (field.type === 'email') rules.push(emailRule);
                ruleBuilders[field.name] = buildValidator(rules);
            });
        });
        return ruleBuilders;
    }, [steps]);

    const handleChange = (name, value) => {
        setFormState((prev) => ({ ...prev, [name]: value }));
        if (errors[name]) {
            setErrors((prev) => ({ ...prev, [name]: null }));
        }
    };

    const validateStep = (index) => {
        const step = steps[index];
        if (!step) return true;
        const stepErrors = {};
        step.fields.forEach((field) => {
            const validator = validators[field.name];
            if (!validator) return;
            const error = validator(formState[field.name]);
            if (error) {
                stepErrors[field.name] = error;
            }
            if (field.name === 'amount' && selectedOptionMeta) {
                const amountValue = Number(formState[field.name] ?? 0);
                if (selectedOptionMeta.min && amountValue < Number(selectedOptionMeta.min)) {
                    stepErrors[field.name] = 'min';
                }
                if (selectedOptionMeta.max && amountValue > Number(selectedOptionMeta.max)) {
                    stepErrors[field.name] = 'max';
                }
            }
        });
        setErrors(stepErrors);
        return Object.keys(stepErrors).length === 0;
    };

    const nextStep = () => {
        if (!validateStep(currentStep)) return;
        setCurrentStep((prev) => Math.min(prev + 1, steps.length - 1));
    };

    const prevStep = () => setCurrentStep((prev) => Math.max(prev - 1, 0));

    const selectedOptionMeta = useMemo(() => {
        const gatewayField = steps.flatMap((step) => step.fields).find((field) => field.name === 'gateway');
        if (!gatewayField) return null;
        return gatewayField.options?.find((option) => option.value === formState.gateway)?.meta ?? null;
    }, [steps, formState.gateway]);

    const calculatedPreview = useMemo(() => {
        const amount = Number(formState.amount || 0);
        const gatewayMeta = selectedOptionMeta;
        if (!gatewayMeta || !amount) {
            return {
                amount: formatCurrency(amount || 0, currency, locale),
                conversion: formatCurrency(0, gatewayMeta?.currency || currency, locale),
                fees: formatCurrency(0, gatewayMeta?.currency || currency, locale),
                total: formatCurrency(amount || 0, currency, locale),
            };
        }
        const conversionAmount = amount * Number(gatewayMeta.rate || 1);
        const percentFee = (conversionAmount / 100) * Number(gatewayMeta.percentCharge || 0);
        const totalFees = Number(gatewayMeta.fixedCharge || 0) + percentFee;
        const willGet = conversionAmount - totalFees;
        return {
            amount: formatCurrency(amount, currency, locale),
            conversion: formatCurrency(conversionAmount, gatewayMeta.currency || currency, locale),
            fees: formatCurrency(totalFees, gatewayMeta.currency || currency, locale),
            total: formatCurrency(amount, currency, locale),
            willGet: formatCurrency(willGet, gatewayMeta.currency || currency, locale),
        };
    }, [formState.amount, selectedOptionMeta, currency, locale]);

    const syncFormAndSubmit = () => {
        const formElement = document.getElementById(formId);
        if (!formElement) return;
        Object.entries(formState).forEach(([name, value]) => {
            let input = formElement.querySelector(`[name="${name}"]`);
            if (!input) {
                input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                formElement.appendChild(input);
            }
            input.value = value;
        });
        if (csrfToken) {
            let tokenInput = formElement.querySelector('input[name="_token"]');
            if (!tokenInput) {
                tokenInput = document.createElement('input');
                tokenInput.type = 'hidden';
                tokenInput.name = '_token';
                tokenInput.value = csrfToken;
                formElement.appendChild(tokenInput);
            }
        }
        formElement.submit();
    };

    const handleSubmit = () => {
        if (!validateStep(currentStep)) return;
        setIsSubmitting(true);
        syncFormAndSubmit();
    };

    return (
        <div className="space-y-8 rounded-3xl bg-white/80 p-8 shadow-2xl ring-1 ring-slate-100 backdrop-blur dark:bg-slate-900/70 dark:ring-slate-700">
            <div>
                <p className="text-sm font-semibold uppercase tracking-wide text-primary-500">{flow}</p>
                <h2 className="mt-2 text-3xl font-bold text-slate-900 dark:text-white">{title}</h2>
                {subtitle && <p className="mt-2 text-slate-500 dark:text-slate-300">{subtitle}</p>}
            </div>

            <StepProgress steps={steps} currentStep={currentStep} />

            <div className="grid gap-6 lg:grid-cols-[2fr_1fr]">
                <div className="space-y-6">
                    {steps[currentStep]?.fields.map((field) => {
                        const value = formState[field.name] ?? '';
                        const fieldId = `${flow}-${field.name}`;
                        return (
                            <div key={field.name}>
                                <label htmlFor={fieldId} className="block text-sm font-semibold text-slate-600 dark:text-slate-200">
                                    {field.label}
                                </label>
                                {field.type === 'select' ? (
                                    <select
                                        id={fieldId}
                                        className="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-700 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                                        value={value}
                                        onChange={(event) => handleChange(field.name, event.target.value)}
                                    >
                                        <option value="">{field.placeholder}</option>
                                        {field.options?.map((option) => (
                                            <option key={option.value} value={option.value}>
                                                {option.label}
                                            </option>
                                        ))}
                                    </select>
                                ) : (
                                    <input
                                        id={fieldId}
                                        type={field.type === 'number' ? 'number' : field.type}
                                        className="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                                        value={value}
                                        min={field.rules?.min}
                                        max={field.rules?.max}
                                        placeholder={field.placeholder}
                                        onChange={(event) => handleChange(field.name, event.target.value)}
                                    />
                                )}
                                <InlineValidation error={errors[field.name]} />
                                {field.helper && <p className="mt-2 text-xs text-slate-500 dark:text-slate-400">{field.helper}</p>}
                            </div>
                        );
                    })}
                    <div className="flex items-center gap-3">
                        <button
                            type="button"
                            className="inline-flex items-center gap-2 rounded-full border border-slate-300 px-6 py-2 text-sm font-semibold text-slate-600 transition hover:border-primary-200 hover:text-primary-600 disabled:opacity-50"
                            onClick={prevStep}
                            disabled={currentStep === 0}
                        >
                            <i className="las la-arrow-left"></i>
                            Previous
                        </button>
                        {currentStep < steps.length - 1 ? (
                            <button
                                type="button"
                                className="inline-flex items-center gap-2 rounded-full bg-primary-600 px-6 py-2 text-sm font-semibold text-white shadow-lg transition hover:bg-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200"
                                onClick={nextStep}
                            >
                                Next
                                <i className="las la-arrow-right"></i>
                            </button>
                        ) : (
                            <button
                                type="button"
                                className="inline-flex items-center gap-2 rounded-full bg-primary-600 px-6 py-2 text-sm font-semibold text-white shadow-lg transition hover:bg-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200"
                                onClick={handleSubmit}
                                disabled={isSubmitting}
                            >
                                {isSubmitting ? 'Submitting…' : 'Submit'}
                                {isSubmitting && <i className="las la-spinner animate-spin"></i>}
                            </button>
                        )}
                    </div>
                </div>
                <aside className="space-y-4 rounded-3xl bg-slate-50/80 p-6 dark:bg-slate-800/60">
                    <h3 className="text-sm font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300">
                        {meta.previewTitle || 'Preview'}
                    </h3>
                    <div className="space-y-3 text-sm text-slate-600 dark:text-slate-300">
                        <div className="flex items-center justify-between">
                            <span>{meta.labels?.amount || 'Amount'}</span>
                            <strong>{calculatedPreview.amount}</strong>
                        </div>
                        <div className="flex items-center justify-between">
                            <span>{meta.labels?.conversion || 'Conversion'}</span>
                            <strong>{calculatedPreview.conversion}</strong>
                        </div>
                        <div className="flex items-center justify-between">
                            <span>{meta.labels?.fees || 'Fees & Charges'}</span>
                            <strong>{calculatedPreview.fees}</strong>
                        </div>
                        {calculatedPreview.willGet && (
                            <div className="flex items-center justify-between text-success">
                                <span>{meta.labels?.willGet || 'Recipient will get'}</span>
                                <strong>{calculatedPreview.willGet}</strong>
                            </div>
                        )}
                        <div className="flex items-center justify-between text-warning">
                            <span>{meta.labels?.total || 'Total Payable'}</span>
                            <strong>{calculatedPreview.total}</strong>
                        </div>
                    </div>
                    <div className="rounded-2xl bg-white/70 p-4 text-xs text-slate-500 shadow-sm dark:bg-slate-900/40 dark:text-slate-400">
                        <p>{meta.helper || 'Amounts update in real-time as you adjust the form.'}</p>
                        {selectedOptionMeta && (
                            <ul className="mt-3 space-y-1">
                                <li>
                                    <strong>{meta.labels?.limit || 'Limit'}:</strong>{' '}
                                    {`${selectedOptionMeta.min || '0'} - ${selectedOptionMeta.max || '∞'} ${currency}`}
                                </li>
                                <li>
                                    <strong>{meta.labels?.rate || 'Rate'}:</strong>{' '}
                                    {`1 ${currency} = ${(Number(selectedOptionMeta.rate || 1)).toFixed(selectedOptionMeta.isCrypto ? 8 : 2)} ${selectedOptionMeta.currency || currency}`}
                                </li>
                            </ul>
                        )}
                    </div>
                </aside>
            </div>
        </div>
    );
};

export default FlowWizardApp;
