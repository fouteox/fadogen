import { ErrorMessage, Field, Label } from '@/components/ui/fieldset';
import { Select } from '@/components/ui/select';
import { fadeInAnimation } from '@/constants/animations';
import {
    BaseFormSectionProps,
    QueueDriverValue,
    QueueHandlers,
    QueueTypeValue,
    SelectChangeEvent,
    SetDataMethod,
} from '@/types';
import clsx from 'clsx';
import { AnimatePresence, motion } from 'motion/react';
import { useTranslation } from 'react-i18next';

interface QueueConfigurationProps extends BaseFormSectionProps, QueueHandlers {
    modifiedFields?: string[];
}

// Type corrigé pour QueueDriverSelectorProps
type QueueDriverSelectorProps = {
    data: BaseFormSectionProps['data'];
    setData: SetDataMethod;
    queueType: QueueTypeValue;
    modifiedFields?: string[];
};

const QueueDriverSelector = ({
    data,
    setData,
    queueType,
    modifiedFields = [],
}: QueueDriverSelectorProps) => {
    const { t } = useTranslation();

    const handleDriverChange = (e: SelectChangeEvent) => {
        setData('queue_driver', e.target.value as QueueDriverValue);
    };

    // Vérifier si un champ a été modifié automatiquement
    const isFieldAutoDetected = (field: string): boolean => {
        return modifiedFields.includes(field);
    };

    return (
        <Field>
            <div className="flex items-center justify-between">
                <Label>{t('laravel.queue_driver')}</Label>
            </div>
            <Select
                name="queue_driver"
                value={data.queue_driver ?? ''}
                onChange={handleDriverChange}
                className={clsx(
                    isFieldAutoDetected('queue_driver') &&
                        'border-blue-500 dark:border-blue-500',
                )}
            >
                <option value="valkey">Valkey ({t('Recommended')})</option>
                <option value="redis">Redis</option>
                {queueType === 'native' && (
                    <option value="database">Database</option>
                )}
            </Select>
        </Field>
    );
};

export const QueueConfiguration = ({
    data,
    setData,
    errors,
    handleQueueChange,
    modifiedFields = [],
}: QueueConfigurationProps) => {
    const { t } = useTranslation();

    const currentQueueType = data.queue_type;

    // Correction de l'erreur TS2367 en vérifiant le type d'une manière différente
    const showDriverSelector = !!currentQueueType; // currentQueueType est défini et non falsy

    // Vérifier si un champ a été modifié automatiquement
    const isFieldAutoDetected = (field: string): boolean => {
        return modifiedFields.includes(field);
    };

    return (
        <>
            <Field>
                <div className="flex items-center justify-between">
                    <Label>{t('laravel.queue_service')}</Label>
                </div>
                <Select
                    name="queue_type"
                    value={currentQueueType ?? 'none'}
                    onChange={handleQueueChange}
                    required
                    invalid={!!errors.queue_type}
                    className={clsx(
                        isFieldAutoDetected('queue_type') &&
                            'border-blue-500 dark:border-blue-500',
                    )}
                >
                    <option value="none">{t('None')}</option>
                    <option value="horizon">
                        Horizon ({t('Recommended')})
                    </option>
                    <option value="native">Queues native</option>
                </Select>
                {errors.queue_type && (
                    <ErrorMessage>{errors.queue_type}</ErrorMessage>
                )}
            </Field>

            <AnimatePresence mode="wait">
                {showDriverSelector && currentQueueType && (
                    <motion.div {...fadeInAnimation}>
                        <QueueDriverSelector
                            data={data}
                            setData={setData}
                            queueType={currentQueueType}
                            modifiedFields={modifiedFields}
                        />
                    </motion.div>
                )}
            </AnimatePresence>
        </>
    );
};
