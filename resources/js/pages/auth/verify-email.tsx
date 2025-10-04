import { Button } from '@/components/ui/button';
import { Fieldset, Legend } from '@/components/ui/fieldset';
import { Text, TextLink } from '@/components/ui/text';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import { useTranslation } from 'react-i18next';
import { store } from '@/actions/App/Http/Controllers/Auth/EmailVerificationNotificationController';
import { logout } from '@/routes';

export default function VerifyEmail({ status }: { status?: string }) {
    const { t } = useTranslation();

    const { processing, submit } = useForm({});

    const verifyEmail: FormEventHandler = (e) => {
        e.preventDefault();

        submit(store());
    };

    return (
        <>
            <Head title={t('Email Verification')} />

            <form onSubmit={verifyEmail} className={'mx-auto w-full sm:max-w-md'}>
                <Fieldset>
                    <Legend className={'text-center sm:text-xl'}>{t('Email Verification')}</Legend>

                    <Text>
                        {t(
                            "Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn't receive the email, we will gladly send you another.",
                        )}
                    </Text>
                </Fieldset>

                {status === 'verification-link-sent' && (
                    <div className="mb-4 text-sm font-medium text-green-600 dark:text-green-400">
                        {t('A new verification link has been sent to the email address you provided during registration.')}
                    </div>
                )}

                <div className="mt-4 flex items-center justify-between">
                    <Button type="submit" disabled={processing}>
                        {t('Resend Verification Email')}
                    </Button>

                    <TextLink href={logout()} method="post" as="button">
                        {t('Log Out')}
                    </TextLink>
                </div>
            </form>
        </>
    );
}
