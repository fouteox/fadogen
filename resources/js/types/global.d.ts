import '@inertiajs/core';
import type { PageProps } from './index';

declare module '@inertiajs/core' {
    interface InertiaConfig {
        sharedPageProps: PageProps;
    }
}
