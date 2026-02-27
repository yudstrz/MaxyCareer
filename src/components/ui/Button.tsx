import React from 'react';

interface ButtonProps extends React.ButtonHTMLAttributes<HTMLButtonElement> {
    variant?: 'primary' | 'secondary' | 'ghost';
    size?: 'sm' | 'md' | 'lg';
}

export const Button: React.FC<ButtonProps> = ({
    children,
    variant = 'primary',
    size = 'md',
    className = '',
    ...props
}) => {
    const baseStyles = 'inline-flex items-center justify-center rounded-lg font-medium transition-all focus:outline-none disabled:opacity-50 disabled:pointer-events-none active:scale-95';

    const variants = {
        primary: 'bg-primary-600 hover:bg-primary-500 text-white shadow-lg shadow-primary-600/20 hover:shadow-primary-600/40',
        secondary: 'bg-gray-800/80 hover:bg-gray-700 text-white border border-white/5',
        ghost: 'bg-transparent hover:bg-white/5 text-gray-300 hover:text-white',
    };

    const sizes = {
        sm: 'px-3 py-1.5 text-xs',
        md: 'px-4 py-2 text-sm',
        lg: 'px-6 py-3 text-base',
    };

    return (
        <button
            className={`${baseStyles} ${variants[variant]} ${sizes[size]} ${className}`}
            {...props}
        >
            {children}
        </button>
    );
};
