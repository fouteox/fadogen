import DiscordLink from '@/components/discord-link';
import GithubLink from '@/components/github-link';
import LanguageSwitcher from '@/components/language-switcher';
import SwitchThemeButton from '@/components/switch-theme-button';
import { Avatar } from '@/components/ui/avatar';
import { Dropdown, DropdownButton, DropdownDivider, DropdownItem, DropdownLabel, DropdownMenu } from '@/components/ui/dropdown';
import { Navbar, NavbarItem, NavbarSection, NavbarSpacer } from '@/components/ui/navbar';
import { Sidebar, SidebarBody, SidebarItem, SidebarSection } from '@/components/ui/sidebar';
import { StackedLayout } from '@/components/ui/stacked-layout';
import { usePage } from '@inertiajs/react';
import { LogOut, User } from 'lucide-react';
import { PropsWithChildren } from 'react';
import { useTranslation } from 'react-i18next';
import { logout, welcome } from '@/routes';
import { index } from '@/routes/generator';
import { NavItem } from '@/types';
import { edit } from '@/routes/profile';

const navItems: NavItem[] = [
    { title: 'Home', href: welcome().url },
    { title: 'Generate', href: index().url },
    // { label: 'Deploy', href: route('deploy', {}, false) },
];

export default function BaseLayout({ children }: PropsWithChildren) {
    const { t } = useTranslation();
    const user = usePage().props.auth.user;

    // When server-side rendering, we only render the layout on the client...
    if (typeof window === 'undefined') {
        return null;
    }

    const currentPath = window.location.pathname;

    return (
        <StackedLayout
            navbar={
                <Navbar className={'mx-auto max-w-6xl'}>
                    <NavbarSection className="max-lg:hidden">
                        {navItems.map((item) => (
                            <NavbarItem key={item.href.url} href={item.href.url} current={currentPath === item.href.url}>
                                {t(item.title)}
                            </NavbarItem>
                        ))}
                    </NavbarSection>
                    <NavbarSpacer />
                    <NavbarSection>
                        <LanguageSwitcher />

                        <SwitchThemeButton />

                        <DiscordLink />

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
                                <DropdownMenu className="min-w-48" anchor="bottom end">
                                    <DropdownItem href={edit().url}>
                                        <User data-slot="icon" />
                                        <DropdownLabel>{t('Profile')}</DropdownLabel>
                                    </DropdownItem>
                                    <DropdownDivider />
                                    <DropdownItem method="post" href={logout().url} type={'button'}>
                                        <LogOut data-slot="icon" />
                                        <DropdownLabel>{t('Log Out')}</DropdownLabel>
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
                            {navItems.map((item) => (
                                <SidebarItem key={item.href.url} href={item.href.url}>
                                    {t(item.title)}
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
