export const required = (value) => {
    if (value === null || value === undefined || value === '') {
        return 'required';
    }
    return null;
};

export const numeric = (value) => {
    if (value === null || value === undefined || value === '') {
        return null;
    }
    return Number.isNaN(Number(value)) ? 'numeric' : null;
};

export const minValue = (min) => (value) => {
    if (value === null || value === undefined || value === '') {
        return null;
    }
    return Number(value) < Number(min) ? 'min' : null;
};

export const maxValue = (max) => (value) => {
    if (value === null || value === undefined || value === '') {
        return null;
    }
    return Number(value) > Number(max) ? 'max' : null;
};

export const email = (value) => {
    if (!value) {
        return null;
    }
    const pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return pattern.test(value) ? null : 'email';
};

export const buildValidator = (rules = []) => (value) => {
    for (const rule of rules) {
        const result = rule(value);
        if (result) {
            return result;
        }
    }
    return null;
};

export default {
    required,
    numeric,
    minValue,
    maxValue,
    email,
    buildValidator,
};
