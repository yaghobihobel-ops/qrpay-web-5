import React from 'react';

const StepProgress = ({ steps, currentStep }) => (
    <div className="flex items-center justify-between gap-3">
        {steps.map((step, index) => {
            const isActive = index === currentStep;
            const isCompleted = index < currentStep;
            return (
                <div key={step.title} className="flex-1">
                    <div className="flex items-center gap-3">
                        <div
                            className={`flex h-9 w-9 items-center justify-center rounded-full border text-sm font-semibold transition ${
                                isActive
                                    ? 'border-primary-500 bg-primary-100 text-primary-600'
                                    : isCompleted
                                    ? 'border-primary-400 bg-primary-500 text-white'
                                    : 'border-slate-300 bg-white text-slate-500'
                            }`}
                        >
                            {index + 1}
                        </div>
                        <div className="text-left">
                            <p className={`text-xs font-semibold uppercase tracking-wide ${isActive ? 'text-primary-600' : 'text-slate-500'}`}>
                                {step.title}
                            </p>
                            <p className="text-xs text-slate-400">{step.description}</p>
                        </div>
                    </div>
                    {index < steps.length - 1 && (
                        <div className={`ml-4 mt-2 h-1 rounded-full ${isCompleted ? 'bg-primary-500' : 'bg-slate-200'}`}></div>
                    )}
                </div>
            );
        })}
    </div>
);

export default StepProgress;
