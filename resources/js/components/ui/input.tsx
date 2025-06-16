import * as Headless from '@headlessui/react';
import clsx from 'clsx';
import type React from 'react';
import { forwardRef } from 'react';

export function InputGroup({
    children,
}: React.ComponentPropsWithoutRef<'span'>) {
    return (
        <span
            data-slot="control"
            className={clsx(
                'relative isolate block',
                'has-[[data-slot=icon]:first-child]:[&_input]:pl-10 has-[[data-slot=icon]:last-child]:[&_input]:pr-10 sm:has-[[data-slot=icon]:first-child]:[&_input]:pl-8 sm:has-[[data-slot=icon]:last-child]:[&_input]:pr-8',
                '*:data-[slot=icon]:pointer-events-none *:data-[slot=icon]:absolute *:data-[slot=icon]:top-3 *:data-[slot=icon]:z-10 *:data-[slot=icon]:size-5 sm:*:data-[slot=icon]:top-2.5 sm:*:data-[slot=icon]:size-4',
                '[&>[data-slot=icon]:first-child]:left-3 sm:[&>[data-slot=icon]:first-child]:left-2.5 [&>[data-slot=icon]:last-child]:right-3 sm:[&>[data-slot=icon]:last-child]:right-2.5',
                '*:data-[slot=icon]:text-zinc-500 dark:*:data-[slot=icon]:text-zinc-400',
            )}
        >
            {children}
        </span>
    );
}

const dateTypes = ['date', 'datetime-local', 'month', 'time', 'week'];
type DateType = (typeof dateTypes)[number];

export const Input = forwardRef(function Input(
    {
        className,
        isLoading = false,
        ...props
    }: {
        className?: string;
        isLoading?: boolean;
        type?:
            | 'email'
            | 'number'
            | 'password'
            | 'search'
            | 'tel'
            | 'text'
            | 'url'
            | DateType;
    } & Omit<Headless.InputProps, 'as' | 'className'>,
    ref: React.ForwardedRef<HTMLInputElement>,
) {
    return (
        <span
            data-slot="control"
            className={clsx([
                className,
                // Basic layout
                'relative block w-full',
                // Background color + shadow applied to inset pseudo element, so shadow blends with border in light mode
                'before:absolute before:inset-px before:rounded-[calc(var(--radius-lg)-1px)] before:bg-white before:shadow-sm',
                // Background color is moved to control and shadow is removed in dark mode so hide `before` pseudo
                'dark:before:hidden',
                // Focus ring
                'after:pointer-events-none after:absolute after:inset-0 after:rounded-lg after:ring-transparent after:ring-inset sm:focus-within:after:ring-2 sm:focus-within:after:ring-blue-500',
                // Disabled state
                'has-data-disabled:opacity-50 has-data-disabled:before:bg-zinc-950/5 has-data-disabled:before:shadow-none',
                // Invalid state
                'has-data-invalid:before:shadow-red-500/10',
            ])}
        >
            {isLoading && (
                <div className="absolute top-1/2 right-3 z-10 -translate-y-1/2 sm:right-2.5">
                    <svg
                        className="h-5 w-5 animate-spin text-zinc-500 sm:h-4 sm:w-4 dark:text-zinc-400"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle
                            className="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            strokeWidth="4"
                        ></circle>
                        <path
                            className="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                        ></path>
                    </svg>
                </div>
            )}
            <Headless.Input
                ref={ref}
                disabled={isLoading || props.disabled}
                data-is-loading={isLoading || undefined}
                {...props}
                className={clsx([
                    // Date classes
                    props.type &&
                        dateTypes.includes(props.type) && [
                            '[&::-webkit-datetime-edit-fields-wrapper]:p-0',
                            '[&::-webkit-date-and-time-value]:min-h-[1.5em]',
                            '[&::-webkit-datetime-edit]:inline-flex',
                            '[&::-webkit-datetime-edit]:p-0',
                            '[&::-webkit-datetime-edit-year-field]:p-0',
                            '[&::-webkit-datetime-edit-month-field]:p-0',
                            '[&::-webkit-datetime-edit-day-field]:p-0',
                            '[&::-webkit-datetime-edit-hour-field]:p-0',
                            '[&::-webkit-datetime-edit-minute-field]:p-0',
                            '[&::-webkit-datetime-edit-second-field]:p-0',
                            '[&::-webkit-datetime-edit-millisecond-field]:p-0',
                            '[&::-webkit-datetime-edit-meridiem-field]:p-0',
                        ],
                    // Basic layout
                    'relative block w-full appearance-none rounded-lg px-[calc(--spacing(3.5)-1px)] py-[calc(--spacing(2.5)-1px)] sm:px-[calc(--spacing(3)-1px)] sm:py-[calc(--spacing(1.5)-1px)]',
                    // Typography
                    'text-base/6 text-zinc-950 placeholder:text-zinc-500 sm:text-sm/6 dark:text-white',
                    // Border
                    'border border-zinc-950/10 data-hover:border-zinc-950/20 dark:border-white/10 dark:data-hover:border-white/20',
                    // Background color
                    'bg-transparent dark:bg-white/5',
                    // Hide default focus styles
                    'focus:outline-hidden',
                    // Invalid state
                    'data-invalid:border-red-500 data-invalid:data-hover:border-red-500 dark:data-invalid:border-red-500 dark:data-invalid:data-hover:border-red-500',
                    // Disabled state
                    'data-disabled:border-zinc-950/20 dark:data-disabled:border-white/15 dark:data-disabled:bg-white/[2.5%] dark:data-hover:data-disabled:border-white/15',
                    // System icons
                    'dark:[color-scheme:dark]',
                    'data-[is-loading=true]:pr-10 sm:data-[is-loading=true]:pr-8',
                ])}
            />
        </span>
    );
});
