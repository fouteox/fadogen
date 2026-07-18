import * as Headless from '@headlessui/react';
import { Link as InertiaLink, type InertiaLinkProps } from '@inertiajs/react';
import React, { forwardRef } from 'react';

export const Link = forwardRef(function Link(props: InertiaLinkProps, ref: React.ForwardedRef<HTMLAnchorElement>) {
    return (
        <Headless.DataInteractive>
            <InertiaLink {...props} ref={ref} />
        </Headless.DataInteractive>
    );
});
