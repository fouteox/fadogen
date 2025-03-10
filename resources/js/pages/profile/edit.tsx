import AppHead from '@/components/app-head';
import { Divider } from '@/components/ui/divider';
import { PageProps } from '@/types';
import { useTranslation } from 'react-i18next';
import DeleteUserForm from './Partials/delete-user-form';
import UpdatePasswordForm from './Partials/update-password-form';
import UpdateProfileInformationForm from './Partials/update-profile-information-form';

export default function Edit({
    mustVerifyEmail,
    status,
}: PageProps<{ mustVerifyEmail: boolean; status?: string }>) {
    const { t } = useTranslation();

    return (
        <>
            <AppHead title={t('Profile')} />

            <Divider className="my-10 mt-6" />

            <UpdateProfileInformationForm
                mustVerifyEmail={mustVerifyEmail}
                status={status}
            />

            <Divider className="my-10" soft />

            <UpdatePasswordForm />

            <Divider className="my-10" soft />

            <DeleteUserForm />
        </>
    );
}
