import { Button } from '@/components/ui/button';
import {
    ErrorMessage,
    Field,
    FieldGroup,
    Label,
} from '@/components/ui/fieldset';
import { Input } from '@/components/ui/input';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import { useTranslation } from 'react-i18next';

export default function ResetPassword({
    token,
    email,
}: {
    token: string;
    email: string;
}) {
    const { t } = useTranslation();

    const { data, setData, post, processing, errors, reset } = useForm({
        token: token,
        email: email,
        password: '',
        password_confirmation: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('password.store'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <>
            <Head title={t('Reset Password')} />

            <form onSubmit={submit} className={'mx-auto w-full sm:max-w-md'}>
                <FieldGroup>
                    <Field>
                        <Label>{t('Email')}</Label>
                        <Input
                            name="Email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            required
                            disabled
                            invalid={!!errors.email}
                            type="email"
                        />
                        {errors.email && (
                            <ErrorMessage>{errors.email}</ErrorMessage>
                        )}
                    </Field>

                    <Field>
                        <Label>{t('New Password')}</Label>
                        <Input
                            type="password"
                            name="password"
                            value={data.password}
                            onChange={(e) =>
                                setData('password', e.target.value)
                            }
                            required
                            autoFocus
                            autoComplete="new-password"
                            invalid={!!errors.password}
                        />
                        {errors.password && (
                            <ErrorMessage>{errors.password}</ErrorMessage>
                        )}
                    </Field>

                    <Field>
                        <Label>{t('Confirm Password')}</Label>
                        <Input
                            type="password"
                            name="password_confirmation"
                            value={data.password_confirmation}
                            onChange={(e) =>
                                setData('password_confirmation', e.target.value)
                            }
                            required
                            autoComplete="new-password"
                            invalid={!!errors.password_confirmation}
                        />
                        {errors.password_confirmation && (
                            <ErrorMessage>
                                {errors.password_confirmation}
                            </ErrorMessage>
                        )}
                    </Field>

                    <div className="mt-4 flex justify-end">
                        <Button type="submit" disabled={processing}>
                            {t('Reset Password')}
                        </Button>
                    </div>
                </FieldGroup>
            </form>
        </>
    );
}
