import { Head } from '@inertiajs/react';
import { Heading } from '@/components/ui/heading';

const Site = ({ title }: { title: string }) => {
    return (
        <>
            <Head title={title}></Head>

            <Heading>{title}</Heading>
        </>
    );
};

export default Site;
