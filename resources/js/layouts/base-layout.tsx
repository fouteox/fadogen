import DiscordLink from '@/components/discord-link';
import GithubLink from '@/components/github-link';
import LanguageSwitcher from '@/components/language-switcher';
import SwitchThemeButton from '@/components/switch-theme-button';
import { Navbar, NavbarItem, NavbarSection, NavbarSpacer } from '@/components/ui/navbar';
import { Sidebar, SidebarBody, SidebarItem, SidebarSection } from '@/components/ui/sidebar';
import { StackedLayout } from '@/components/ui/stacked-layout';
import { PropsWithChildren } from 'react';
import { useTranslation } from 'react-i18next';
import { welcome } from '@/routes';
import { index } from '@/routes/generator';
import { NavItem } from '@/types';

const navItems: NavItem[] = [
    { title: 'Home', href: welcome().url },
    { title: 'Generate', href: index().url },
    // { label: 'Deploy', href: route('deploy', {}, false) },
];

export default function BaseLayout({ children }: PropsWithChildren) {
    const { t } = useTranslation();

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
