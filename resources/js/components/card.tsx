import { Link } from '@/components/ui/link';
import { Text } from '@/components/ui/text';
import clsx from 'clsx';
import type React from 'react';

const sizes = {
    xs: 'sm:max-w-xs',
    sm: 'sm:max-w-sm',
    md: 'sm:max-w-md',
    lg: 'sm:max-w-lg',
    xl: 'sm:max-w-xl',
    '2xl': 'sm:max-w-2xl',
    '3xl': 'sm:max-w-3xl',
    '4xl': 'sm:max-w-4xl',
    '5xl': 'sm:max-w-5xl',
};

type CardBaseProps = {
    size?: keyof typeof sizes;
    className?: string;
    children: React.ReactNode;
    href?: string;
};

type CardProps = CardBaseProps &
    (
        | Omit<React.ComponentPropsWithoutRef<'div'>, 'href'>
        | Omit<
              React.ComponentPropsWithoutRef<typeof Link>,
              'href' | 'className'
          >
    );

export function Card({
    size = 'md',
    className,
    children,
    href,
    ...props
}: CardProps) {
    const classes = clsx(
        className,
        sizes[size],
        'w-full rounded-2xl bg-white p-6 shadow-lg ring-1 ring-zinc-950/10 sm:p-6 dark:bg-zinc-900 dark:ring-white/10 forced-colors:outline',
    );

    if (href) {
        return (
            <Link
                href={href}
                {...(props as Omit<
                    React.ComponentPropsWithoutRef<typeof Link>,
                    'href' | 'className'
                >)}
                className={classes}
            >
                {children}
            </Link>
        );
    }

    return (
        <div
            {...(props as React.ComponentPropsWithoutRef<'div'>)}
            className={classes}
        >
            {children}
        </div>
    );
}

export function CardHeader({
    className,
    ...props
}: React.ComponentPropsWithoutRef<'div'>) {
    return <div {...props} className={clsx(className)} />;
}

export function CardTitle({
    className,
    ...props
}: React.ComponentPropsWithoutRef<'h3'>) {
    return (
        <h3
            {...props}
            className={clsx(
                className,
                'text-lg/6 font-semibold text-balance text-zinc-950 sm:text-base/6 dark:text-white',
            )}
        />
    );
}

export function CardDescription({
    className,
    ...props
}: { className?: string } & Omit<
    React.ComponentPropsWithoutRef<typeof Text>,
    'className'
>) {
    return <Text {...props} className={clsx(className, 'mt-2 text-pretty')} />;
}

export function CardBody({
    className,
    ...props
}: React.ComponentPropsWithoutRef<'div'>) {
    return <div {...props} className={clsx(className, 'mt-6')} />;
}

export function CardFooter({
    className,
    ...props
}: React.ComponentPropsWithoutRef<'div'>) {
    return (
        <div
            {...props}
            className={clsx(
                className,
                'mt-6 flex flex-col-reverse items-center justify-end gap-3 *:w-full sm:flex-row sm:*:w-auto',
            )}
        />
    );
}
