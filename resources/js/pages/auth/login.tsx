import { Button } from '@/components/ui/button';
import {
    ErrorMessage,
    Field,
    FieldGroup,
    Fieldset,
    Label,
    Legend,
} from '@/components/ui/fieldset';
import { Input } from '@/components/ui/input';
import { Switch } from '@/components/ui/switch';
import { Text, TextLink } from '@/components/ui/text';
import * as Headless from '@headlessui/react';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import { useTranslation } from 'react-i18next';

export default function Login({
    status,
    canResetPassword,
}: {
    status?: string;
    canResetPassword: boolean;
}) {
    const { t } = useTranslation();

    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false as boolean,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <>
            <Head title={t('Log in')} />

            {status && (
                <div className="mb-4 text-sm font-medium text-green-600">
                    {status}
                </div>
            )}

            <form onSubmit={submit} className={'mx-auto w-full sm:max-w-md'}>
                <Fieldset>
                    <Legend className={'text-center sm:text-xl'}>
                        {t('Log in')}
                    </Legend>

                    <FieldGroup>
                        <Field>
                            <Label>{t('Email')}</Label>
                            <Input
                                autoFocus
                                name="Email"
                                value={data.email}
                                onChange={(e) =>
                                    setData('email', e.target.value)
                                }
                                required
                                invalid={!!errors.email}
                                type="email"
                            />
                            {errors.email && (
                                <ErrorMessage>{errors.email}</ErrorMessage>
                            )}
                        </Field>

                        <Field>
                            <Label>{t('Password')}</Label>
                            <Input
                                type="password"
                                name="password"
                                value={data.password}
                                onChange={(e) =>
                                    setData('password', e.target.value)
                                }
                                required
                                autoComplete="current-password"
                                invalid={!!errors.password}
                            />
                            {errors.password && (
                                <ErrorMessage>{errors.password}</ErrorMessage>
                            )}
                        </Field>

                        <div className="flex flex-wrap justify-between gap-2">
                            <Headless.Field className="flex items-center gap-2">
                                <Switch
                                    name="remember"
                                    checked={data.remember}
                                    onChange={(checked) =>
                                        setData('remember', checked)
                                    }
                                />
                                <Label>{t('Remember me')}</Label>
                            </Headless.Field>

                            {canResetPassword && (
                                <TextLink href={route('password.request')}>
                                    {t('Forgot your password?')}
                                </TextLink>
                            )}
                        </div>

                        <Button
                            type="submit"
                            className={'w-full'}
                            disabled={processing}
                        >
                            {t('Log in')}
                        </Button>

                        <div className={'flex gap-2'}>
                            <Text>{t('Not yet register?')}</Text>
                            <TextLink href={route('register')}>
                                {t('Sign Up')}
                            </TextLink>
                        </div>
                    </FieldGroup>
                </Fieldset>
            </form>
        </>
    );
}
