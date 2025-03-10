import { NavbarItem } from '@/components/ui/navbar';
import { MoonIcon, SunIcon } from 'lucide-react';
import { useState } from 'react';

const SwitchThemeButton = () => {
    const [theme, setTheme] = useState<'light' | 'dark'>(
        document.documentElement.classList.contains('dark') ? 'dark' : 'light',
    );

    const toggleTheme = () => {
        const newTheme = theme === 'dark' ? 'light' : 'dark';
        if (newTheme === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
        localStorage.setItem('theme', newTheme);
        setTheme(newTheme);
    };

    return (
        <NavbarItem type={'button'} onClick={toggleTheme}>
            {theme === 'dark' ? (
                <SunIcon className="h-[24px] w-[24px] md:h-[20px] md:w-[20px]" />
            ) : (
                <MoonIcon className="h-[24px] w-[24px] md:h-[20px] md:w-[20px]" />
            )}
        </NavbarItem>
    );
};

export default SwitchThemeButton;
