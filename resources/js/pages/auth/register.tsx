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
import { Text, TextLink } from '@/components/ui/text';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import { useTranslation } from 'react-i18next';

export default function Register() {
    const { t } = useTranslation();

    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('register'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <>
            <Head title={t('Register')} />

            <form onSubmit={submit} className={'mx-auto w-full sm:max-w-md'}>
                <Fieldset>
                    <Legend className={'sm:text-xl'}>Inscription</Legend>

                    <FieldGroup>
                        <Field>
                            <Label>{t('Name')}</Label>
                            <Input
                                name="name"
                                value={data.name}
                                onChange={(e) =>
                                    setData('name', e.target.value)
                                }
                                required
                                autoFocus
                                invalid={!!errors.name}
                                type="text"
                                autoComplete="name"
                            />
                            {errors.name && (
                                <ErrorMessage>{errors.name}</ErrorMessage>
                            )}
                        </Field>

                        <Field>
                            <Label>{t('Email')}</Label>
                            <Input
                                name="Email"
                                value={data.email}
                                onChange={(e) =>
                                    setData('email', e.target.value)
                                }
                                required
                                invalid={!!errors.email}
                                type="email"
                                autoComplete="email"
                            />
                            {errors.email && (
                                <ErrorMessage>{errors.email}</ErrorMessage>
                            )}
                        </Field>

                        <Field>
                            <Label>{t('Password')}</Label>
                            <Input
                                name="password"
                                value={data.password}
                                autoComplete="new-password"
                                onChange={(e) =>
                                    setData('password', e.target.value)
                                }
                                required
                                invalid={!!errors.password}
                                type="password"
                            />
                            {errors.password && (
                                <ErrorMessage>{errors.password}</ErrorMessage>
                            )}
                        </Field>

                        <Field>
                            <Label>{t('Confirm Password')}</Label>
                            <Input
                                name="password_confirmation"
                                value={data.password_confirmation}
                                autoComplete="new-password"
                                onChange={(e) =>
                                    setData(
                                        'password_confirmation',
                                        e.target.value,
                                    )
                                }
                                required
                                invalid={!!errors.password_confirmation}
                                type="password"
                            />
                            {errors.password_confirmation && (
                                <ErrorMessage>
                                    {errors.password_confirmation}
                                </ErrorMessage>
                            )}
                        </Field>

                        <Button
                            type="submit"
                            className={'w-full'}
                            disabled={processing}
                        >
                            {t('Register')}
                        </Button>

                        <div className={'flex gap-2'}>
                            <Text>{t('Already registered?')}</Text>
                            <TextLink href={route('login')}>
                                {t('Login')}
                            </TextLink>
                        </div>
                    </FieldGroup>
                </Fieldset>
            </form>
        </>
    );
}
