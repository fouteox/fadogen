import { MoonIcon, SunIcon } from 'lucide-react';
import { NavbarItem } from '@/components/ui/navbar';

const SwitchThemeButton = () => {
    const toggleTheme = () => {
        const isDark = document.documentElement.classList.toggle('dark');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
    };

    return (
        <NavbarItem type="button" aria-label="Toggle theme" onClick={toggleTheme}>
            <SunIcon className="hidden h-[24px] w-[24px] md:h-[20px] md:w-[20px] dark:block" />
            <MoonIcon className="h-[24px] w-[24px] md:h-[20px] md:w-[20px] dark:hidden" />
        </NavbarItem>
    );
};

export default SwitchThemeButton;
