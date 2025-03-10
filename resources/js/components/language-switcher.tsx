import {
    Dropdown,
    DropdownButton,
    DropdownItem,
    DropdownLabel,
    DropdownMenu,
} from '@/components/ui/dropdown';
import { NavbarItem } from '@/components/ui/navbar';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';

const LANGUAGES = [
    { code: 'fr', name: 'Français', flag: 'fr' },
    { code: 'en', name: 'English', flag: 'gb' },
    { code: 'de', name: 'Deutsch', flag: 'de' },
    { code: 'es', name: 'Español', flag: 'es' },
] as const;

type Language = (typeof LANGUAGES)[number];

const LanguageSwitcher = () => {
    const { i18n } = useTranslation();
    const [currentLang, setCurrentLang] = useState<Language>(
        LANGUAGES.find((lang) => lang.code === i18n.language) ?? LANGUAGES[0],
    );

    const handleLanguageChange = (language: Language) => {
        void i18n.changeLanguage(language.code);
        setCurrentLang(language);
    };

    return (
        <Dropdown>
            <DropdownButton as={NavbarItem}>
                <span
                    className={`fib fi-${currentLang.flag} h-[24px] w-[24px] rounded-sm md:h-[20px] md:w-[20px]`}
                    role="img"
                    aria-label={`Drapeau ${currentLang.name}`}
                />
            </DropdownButton>
            <DropdownMenu anchor="bottom">
                {LANGUAGES.map((language) => (
                    <DropdownItem
                        key={language.code}
                        onClick={() => handleLanguageChange(language)}
                    >
                        <span
                            data-slot="icon"
                            className={`fib fi-${language.flag} rounded-sm`}
                            role="img"
                            aria-label={`Drapeau ${language.name}`}
                        />
                        <DropdownLabel>{language.name}</DropdownLabel>
                    </DropdownItem>
                ))}
            </DropdownMenu>
        </Dropdown>
    );
};

export default LanguageSwitcher;
