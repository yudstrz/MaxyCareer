import React from 'react';

interface BadgeProps {
    children: React.ReactNode;
    variant?: 'blue' | 'yellow' | 'red' | 'green' | 'gray';
}

const variants = {
    blue: 'bg-blue-500/10 text-blue-400 border-blue-500/20',
    yellow: 'bg-yellow-500/10 text-yellow-400 border-yellow-500/20',
    red: 'bg-red-500/10 text-red-400 border-red-500/20',
    green: 'bg-green-500/10 text-green-400 border-green-500/20',
    gray: 'bg-gray-500/10 text-gray-400 border-gray-500/20',
};

export const Badge: React.FC<BadgeProps> = ({ children, variant = 'blue' }) => {
    return (
        <span className={`px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase tracking-wider border ${variants[variant]}`}>
            {children}
        </span>
    );
};
