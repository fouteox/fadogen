import { Alert, AlertActions, AlertBody, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { ErrorMessage } from '@/components/ui/fieldset';
import { Subheading } from '@/components/ui/heading';
import { Input } from '@/components/ui/input';
import { Text } from '@/components/ui/text';
import { useForm } from '@inertiajs/react';
import { FormEventHandler, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { destroy } from '@/routes/profile';

export default function DeleteUserForm() {
    const { t } = useTranslation();
    const [confirmingUserDeletion, setConfirmingUserDeletion] = useState(false);
    const passwordInput = useRef<HTMLInputElement>(null);

    const { data, setData, submit, processing, reset, errors, clearErrors } = useForm({
        password: '',
    });

    const confirmUserDeletion = () => {
        setConfirmingUserDeletion(true);
    };

    const deleteUser: FormEventHandler = (e) => {
        e.preventDefault();

        submit(destroy(), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
            onError: () => passwordInput.current?.focus(),
            onFinish: () => reset(),
        });
    };

    const closeModal = () => {
        setConfirmingUserDeletion(false);

        clearErrors();
        reset();
    };

    return (
        <section className="max-w-xl space-y-6">
            <div className="space-y-1">
                <Subheading>{t('Delete Account')}</Subheading>
                <Text>
                    {t(
                        'Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.',
                    )}
                </Text>
            </div>

            <Button color="red" onClick={confirmUserDeletion}>
                {t('Delete Account')}
            </Button>

            <Alert open={confirmingUserDeletion} onClose={closeModal} size="lg">
                <form onSubmit={deleteUser} className="p-6">
                    <AlertTitle>{t('Are you sure you want to delete your account?')}</AlertTitle>
                    <AlertDescription>
                        {t(
                            'Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.',
                        )}
                    </AlertDescription>
                    <AlertBody>
                        <Input
                            ref={passwordInput}
                            autoFocus
                            aria-label="Password"
                            placeholder={t('Password')}
                            name="password"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            required
                            invalid={!!errors.password}
                            type="password"
                        />
                        {errors.password && <ErrorMessage>{errors.password}</ErrorMessage>}
                    </AlertBody>
                    <AlertActions>
                        <Button plain onClick={closeModal}>
                            {t('Cancel')}
                        </Button>
                        <Button type="submit" color="red" disabled={processing}>
                            {t('Delete Account')}
                        </Button>
                    </AlertActions>
                </form>
            </Alert>
        </section>
    );
}
