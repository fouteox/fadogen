import { Heading } from '@/components/ui/heading';
import { Head } from '@inertiajs/react';

const Site = ({ title }: { title: string }) => {
    return (
        <>
            <Head title={title}></Head>

            <Heading>{title}</Heading>
        </>
    );
};

export default Site;
