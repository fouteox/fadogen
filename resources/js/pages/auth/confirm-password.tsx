import AppHead from '@/components/app-head';
import { Button } from '@/components/ui/button';
import {
    ErrorMessage,
    Field,
    FieldGroup,
    Label,
} from '@/components/ui/fieldset';
import { Input } from '@/components/ui/input';
import { useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import { useTranslation } from 'react-i18next';

export default function ConfirmPassword() {
    const { t } = useTranslation();

    const { data, setData, post, processing, errors, reset } = useForm({
        password: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('password.confirm'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <>
            <AppHead title={t('Confirm Password')} />

            <div className="mb-4 text-sm text-gray-600 dark:text-gray-400">
                {t(
                    'This is a secure area of the application. Please confirm your password before continuing.',
                )}
            </div>

            <form onSubmit={submit}>
                <FieldGroup>
                    <Field>
                        <Label>{t('Password')}</Label>
                        <Input
                            type="password"
                            name="password"
                            value={data.password}
                            autoFocus
                            onChange={(e) =>
                                setData('password', e.target.value)
                            }
                            required
                            invalid={!!errors.password}
                        />
                        {errors.password && (
                            <ErrorMessage>{errors.password}</ErrorMessage>
                        )}
                    </Field>

                    <div className="flex items-center justify-end gap-4">
                        <Button type="submit" disabled={processing}>
                            {t('Confirm')}
                        </Button>
                    </div>
                </FieldGroup>
            </form>
        </>
    );
}
