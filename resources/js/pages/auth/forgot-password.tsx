import { Button } from '@/components/ui/button';
import { ErrorMessage, Field, FieldGroup, Fieldset, Label, Legend } from '@/components/ui/fieldset';
import { Input } from '@/components/ui/input';
import { Text } from '@/components/ui/text';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import { useTranslation } from 'react-i18next';
import { store } from '@/actions/App/Http/Controllers/Auth/PasswordResetLinkController';

export default function ForgotPassword({ status }: { status?: string }) {
    const { t } = useTranslation();

    const { data, setData, processing, submit, errors } = useForm({
        email: '',
    });

    const forgotPassword: FormEventHandler = (e) => {
        e.preventDefault();

        submit(store());
    };

    return (
        <>
            <Head title={t('Forgot Password')} />

            <form onSubmit={forgotPassword} className={'mx-auto w-full sm:max-w-md'}>
                <Fieldset>
                    <Legend>{t('Forgot Password')}</Legend>
                    <Text>
                        {t(
                            'Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.',
                        )}
                    </Text>

                    <FieldGroup>
                        <Field>
                            <Label>{t('Email')}</Label>
                            <Input
                                autoFocus
                                name="Email"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                required
                                autoComplete="email"
                                invalid={!!errors.email}
                                type="email"
                            />
                            {errors.email && <ErrorMessage>{errors.email}</ErrorMessage>}

                            {status && <div className="mt-3 text-base/6 font-medium text-green-600 sm:text-sm/6 dark:text-green-400">{status}</div>}
                        </Field>

                        <div className="mt-4 flex justify-end">
                            <Button type="submit" disabled={processing}>
                                {t('Email Password Reset Link')}
                            </Button>
                        </div>
                    </FieldGroup>
                </Fieldset>
            </form>
        </>
    );
}
