import * as Headless from '@headlessui/react';
import clsx from 'clsx';
import type React from 'react';

export function Fieldset({
    className,
    ...props
}: { className?: string } & Omit<Headless.FieldsetProps, 'as' | 'className'>) {
    return (
        <Headless.Fieldset
            {...props}
            className={clsx(
                className,
                '*:data-[slot=text]:mt-1 [&>*+[data-slot=control]]:mt-6',
            )}
        />
    );
}

export function Legend({
    className,
    ...props
}: { className?: string } & Omit<Headless.LegendProps, 'as' | 'className'>) {
    return (
        <Headless.Legend
            data-slot="legend"
            {...props}
            className={clsx(
                className,
                'text-base/6 font-semibold text-zinc-950 data-disabled:opacity-50 sm:text-sm/6 dark:text-white',
            )}
        />
    );
}

export function FieldGroup({
    className,
    ...props
}: React.ComponentPropsWithoutRef<'div'>) {
    return (
        <div
            data-slot="control"
            {...props}
            className={clsx(className, 'space-y-8')}
        />
    );
}

export function Field({
    className,
    ...props
}: { className?: string } & Omit<Headless.FieldProps, 'as' | 'className'>) {
    return (
        <Headless.Field
            {...props}
            className={clsx(
                className,
                '[&>[data-slot=label]+[data-slot=control]]:mt-3',
                '[&>[data-slot=label]+[data-slot=description]]:mt-1',
                '[&>[data-slot=description]+[data-slot=control]]:mt-3',
                '[&>[data-slot=control]+[data-slot=description]]:mt-3',
                '[&>[data-slot=control]+[data-slot=error]]:mt-3',
                '[&>[data-slot=control]+[data-slot=info]]:mt-3',
                '[&>[data-slot=control]+[data-slot=success]]:mt-3',
                '[&>[data-slot=control]+[data-slot=warning]]:mt-3',
                '*:data-[slot=label]:font-medium',
            )}
        />
    );
}

export function Label({
    className,
    ...props
}: { className?: string } & Omit<Headless.LabelProps, 'as' | 'className'>) {
    return (
        <Headless.Label
            data-slot="label"
            {...props}
            className={clsx(
                className,
                'text-base/6 text-zinc-950 select-none data-disabled:opacity-50 sm:text-sm/6 dark:text-white',
            )}
        />
    );
}

export function Description({
    className,
    ...props
}: { className?: string } & Omit<
    Headless.DescriptionProps,
    'as' | 'className'
>) {
    return (
        <Headless.Description
            data-slot="description"
            {...props}
            className={clsx(
                className,
                'text-base/6 text-zinc-500 data-disabled:opacity-50 sm:text-sm/6 dark:text-zinc-400',
            )}
        />
    );
}

type MessageVariant = 'error' | 'info' | 'success' | 'warning';

const variantStyles: Record<MessageVariant, string> = {
    error: 'text-red-600 dark:text-red-500',
    info: 'text-blue-600 dark:text-blue-400',
    success: 'text-green-600 dark:text-green-400',
    warning: 'text-amber-600 dark:text-amber-400',
};

export function ErrorMessage({
    className,
    variant = 'error',
    ...props
}: {
    className?: string;
    variant?: MessageVariant;
} & Omit<Headless.DescriptionProps, 'as' | 'className'>) {
    return (
        <Headless.Description
            data-slot={variant}
            {...props}
            className={clsx(
                className,
                'text-base/6 data-disabled:opacity-50 sm:text-sm/6',
                variantStyles[variant],
            )}
        />
    );
}

export function FieldsetInfoMessage({
    className,
    ...props
}: React.PropsWithChildren<{ className?: string }>) {
    return (
        <div
            data-slot="info"
            {...props}
            className={clsx(
                className,
                'mt-3 text-base/6 text-blue-600 data-disabled:opacity-50 sm:text-sm/6 dark:text-blue-400',
            )}
        />
    );
}

export function FieldsetErrorMessage({
    className,
    ...props
}: React.PropsWithChildren<{ className?: string }>) {
    return (
        <div
            data-slot="error"
            {...props}
            className={clsx(
                className,
                'mt-3 text-base/6 text-red-600 data-disabled:opacity-50 sm:text-sm/6 dark:text-red-500',
            )}
        />
    );
}

export const InfoMessage = (
    props: Omit<React.ComponentProps<typeof ErrorMessage>, 'variant'>,
) => <ErrorMessage variant="info" {...props} />;

export const SuccessMessage = (
    props: Omit<React.ComponentProps<typeof ErrorMessage>, 'variant'>,
) => <ErrorMessage variant="success" {...props} />;

export const WarningMessage = (
    props: Omit<React.ComponentProps<typeof ErrorMessage>, 'variant'>,
) => <ErrorMessage variant="warning" {...props} />;
