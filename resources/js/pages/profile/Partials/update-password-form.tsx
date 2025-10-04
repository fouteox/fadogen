import { Button } from '@/components/ui/button';
import { ErrorMessage, Field, FieldGroup, Label } from '@/components/ui/fieldset';
import { Subheading } from '@/components/ui/heading';
import { Input } from '@/components/ui/input';
import { Text } from '@/components/ui/text';
import { Transition } from '@headlessui/react';
import { useForm } from '@inertiajs/react';
import { FormEventHandler, useRef } from 'react';
import { useTranslation } from 'react-i18next';
import { update } from '@/routes/password';

export default function UpdatePasswordForm() {
    const { t } = useTranslation();
    const passwordInput = useRef<HTMLInputElement>(null);
    const currentPasswordInput = useRef<HTMLInputElement>(null);

    const { data, setData, errors, submit, reset, processing, recentlySuccessful } = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const updatePassword: FormEventHandler = (e) => {
        e.preventDefault();

        submit(update(), {
            preserveScroll: true,
            onSuccess: () => reset(),
            onError: (errors) => {
                if (errors.password) {
                    reset('password', 'password_confirmation');
                    passwordInput.current?.focus();
                }

                if (errors.current_password) {
                    reset('current_password');
                    currentPasswordInput.current?.focus();
                }
            },
        });
    };

    return (
        <section className="grid gap-x-8 gap-y-6 sm:grid-cols-2">
            <div className="space-y-1">
                <Subheading>{t('Update Password')}</Subheading>
                <Text>{t('Ensure your account is using a long, random password to stay secure.')}</Text>
            </div>

            <form onSubmit={updatePassword}>
                <FieldGroup>
                    <Field>
                        <Label>{t('Current Password')}</Label>
                        <Input
                            ref={currentPasswordInput}
                            type="password"
                            name="current_password"
                            value={data.current_password}
                            onChange={(e) => setData('current_password', e.target.value)}
                            required
                            autoComplete="current-password"
                            invalid={!!errors.current_password}
                        />
                        {errors.current_password && <ErrorMessage>{errors.current_password}</ErrorMessage>}
                    </Field>

                    <Field>
                        <Label>{t('New Password')}</Label>
                        <Input
                            ref={passwordInput}
                            type="password"
                            name="password"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            required
                            autoComplete="new-password"
                            invalid={!!errors.password}
                        />
                        {errors.password && <ErrorMessage>{errors.password}</ErrorMessage>}
                    </Field>

                    <Field>
                        <Label>{t('Confirm Password')}</Label>
                        <Input
                            type="password"
                            name="password_confirmation"
                            value={data.password_confirmation}
                            onChange={(e) => setData('password_confirmation', e.target.value)}
                            required
                            autoComplete="new-password"
                            invalid={!!errors.password_confirmation}
                        />
                        {errors.password_confirmation && <ErrorMessage>{errors.password_confirmation}</ErrorMessage>}
                    </Field>

                    <div className="flex items-center justify-end gap-4">
                        <Transition
                            show={recentlySuccessful}
                            enter="transition ease-in-out"
                            enterFrom="opacity-0"
                            leave="transition ease-in-out"
                            leaveTo="opacity-0"
                        >
                            <p className="text-sm text-gray-600 dark:text-gray-400">{t('Saved.')}</p>
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
