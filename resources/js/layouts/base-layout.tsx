import GithubLink from '@/components/github-link';
import LanguageSwitcher from '@/components/language-switcher';
import SwitchThemeButton from '@/components/switch-theme-button';
import { Avatar } from '@/components/ui/avatar';
import {
    Dropdown,
    DropdownButton,
    DropdownDivider,
    DropdownItem,
    DropdownLabel,
    DropdownMenu,
} from '@/components/ui/dropdown';
import {
    Navbar,
    NavbarItem,
    NavbarSection,
    NavbarSpacer,
} from '@/components/ui/navbar';
import {
    Sidebar,
    SidebarBody,
    SidebarItem,
    SidebarSection,
} from '@/components/ui/sidebar';
import { StackedLayout } from '@/components/ui/stacked-layout';
import { usePage } from '@inertiajs/react';
import { LogOut, User } from 'lucide-react';
import { PropsWithChildren } from 'react';
import { useTranslation } from 'react-i18next';

const navItems = [
    { label: 'Home', url: route('welcome', {}, false) },
    { label: 'Generate', url: route('generator.index', {}, false) },
    { label: 'Deploy', url: route('deploy', {}, false) },
];

export default function BaseLayout({ children }: PropsWithChildren) {
    const { t } = useTranslation();
    const user = usePage().props.auth.user;
    const { url: currentUrl } = usePage();

    return (
        <StackedLayout
            navbar={
                <Navbar className={'mx-auto max-w-6xl'}>
                    <NavbarSection className="max-lg:hidden">
                        {navItems.map(({ label, url }) => (
                            <NavbarItem
                                key={label}
                                href={url}
                                current={url === currentUrl}
                            >
                                {t(label)}
                            </NavbarItem>
                        ))}
                    </NavbarSection>
                    <NavbarSpacer />
                    <NavbarSection>
                        <LanguageSwitcher />

                        <SwitchThemeButton />

                        <GithubLink />

                        {user && (
                            <Dropdown>
                                <DropdownButton as={NavbarItem}>
                                    <Avatar
                                        square
                                        initials={user.name.slice(0, 2)}
                                        className="rounded-md bg-zinc-900 text-white dark:bg-white dark:text-black"
                                    />
                                </DropdownButton>
                                <DropdownMenu
                                    className="min-w-48"
                                    anchor="bottom end"
                                >
                                    <DropdownItem href={route('profile.edit')}>
                                        <User data-slot="icon" />
                                        <DropdownLabel>
                                            {t('Profile')}
                                        </DropdownLabel>
                                    </DropdownItem>
                                    <DropdownDivider />
                                    <DropdownItem
                                        method="post"
                                        href={route('logout')}
                                        type={'button'}
                                    >
                                        <LogOut data-slot="icon" />
                                        <DropdownLabel>
                                            {t('Log Out')}
                                        </DropdownLabel>
                                    </DropdownItem>
                                </DropdownMenu>
                            </Dropdown>
                        )}
                    </NavbarSection>
                </Navbar>
            }
            sidebar={
                <Sidebar>
                    <SidebarBody>
                        <SidebarSection>
                            {navItems.map(({ label, url }) => (
                                <SidebarItem key={label} href={url}>
                                    {t(label)}
                                </SidebarItem>
                            ))}
                        </SidebarSection>
                    </SidebarBody>
                </Sidebar>
            }
        >
            {children}
        </StackedLayout>
    );
}
