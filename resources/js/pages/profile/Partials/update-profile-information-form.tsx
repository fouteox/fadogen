import { Button } from '@/components/ui/button';
import {
    ErrorMessage,
    Field,
    FieldGroup,
    Label,
} from '@/components/ui/fieldset';
import { Subheading } from '@/components/ui/heading';
import { Input } from '@/components/ui/input';
import { Link } from '@/components/ui/link';
import { Text } from '@/components/ui/text';
import { Transition } from '@headlessui/react';
import { useForm, usePage } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import { useTranslation } from 'react-i18next';

export default function UpdateProfileInformation({
    mustVerifyEmail,
    status,
}: {
    mustVerifyEmail: boolean;
    status?: string;
}) {
    const { t } = useTranslation();
    const user = usePage().props.auth.user;

    const { data, setData, patch, errors, processing, recentlySuccessful } =
        useForm({
            name: user.name,
            email: user.email,
        });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        patch(route('profile.update'));
    };

    return (
        <section className="grid gap-x-8 gap-y-6 sm:grid-cols-2">
            <div className="space-y-1">
                <Subheading>{t('Profile Information')}</Subheading>
                <Text>
                    {t(
                        "Update your account's profile information and email address.",
                    )}
                </Text>
            </div>

            <form onSubmit={submit}>
                <FieldGroup>
                    <Field>
                        <Label>{t('Name')}</Label>
                        <Input
                            name="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            required
                            autoComplete="name"
                            invalid={!!errors.name}
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
                            onChange={(e) => setData('email', e.target.value)}
                            required
                            autoComplete="email"
                            invalid={!!errors.email}
                            type="email"
                        />
                        {errors.email && (
                            <ErrorMessage>{errors.email}</ErrorMessage>
                        )}
                    </Field>

                    {mustVerifyEmail && user.email_verified_at === null && (
                        <div>
                            <p className="mt-2 text-sm text-gray-800 dark:text-gray-200">
                                {t('Your email address is unverified.')}
                                <Link
                                    href={route('verification.send')}
                                    method="post"
                                    as="button"
                                    className="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-hidden dark:text-gray-400 dark:hover:text-gray-100 dark:focus:ring-offset-gray-800"
                                >
                                    {t(
                                        'Click here to re-send the verification email.',
                                    )}
                                </Link>
                            </p>

                            {status === 'verification-link-sent' && (
                                <div className="mt-2 text-sm font-medium text-green-600 dark:text-green-400">
                                    {t(
                                        'A new verification link has been sent to your email address.',
                                    )}
                                </div>
                            )}
                        </div>
                    )}

                    <div className="flex items-center gap-4">
                        <Transition
                            show={recentlySuccessful}
                            enter="transition ease-in-out"
                            enterFrom="opacity-0"
                            leave="transition ease-in-out"
                            leaveTo="opacity-0"
                        >
                            <p className="text-sm text-gray-600 dark:text-gray-400">
                                {t('Saved.')}
                            </p>
                        </Transition>

                        <Button type="submit" disabled={processing}>
                            {t('Save')}
                        </Button>
                    </div>
                </FieldGroup>
            </form>
        </section>
    );
}
