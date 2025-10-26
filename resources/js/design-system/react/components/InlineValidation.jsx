import React from 'react';

const messages = {
    required: 'This field is required',
    numeric: 'Please enter a valid number',
    min: 'Value is too low',
    max: 'Value is too high',
    email: 'Enter a valid email address',
};

const InlineValidation = ({ error }) => {
    if (!error) return null;
    return (
        <p className="mt-2 text-xs font-medium text-danger">
            <i className="las la-exclamation-circle mr-1"></i>
            {messages[error] || error}
        </p>
    );
};

export default InlineValidation;
