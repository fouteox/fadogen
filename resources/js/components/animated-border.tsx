import React from 'react';

const AnimatedBorder: React.FC<{ children: React.ReactNode }> = ({
    children,
}) => {
    return (
        <div className="group relative overflow-hidden rounded-lg p-[1px]">
            <div className="absolute inset-0 rounded-lg border border-neutral-200 transition-[border-width] duration-300 group-hover:border-2 dark:border-neutral-700" />
            <div className="relative rounded-lg">{children}</div>
        </div>
    );
};

export default AnimatedBorder;
