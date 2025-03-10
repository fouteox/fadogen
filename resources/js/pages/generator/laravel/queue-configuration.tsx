import { ErrorMessage, Field, Label } from '@/components/ui/fieldset';
import { Select } from '@/components/ui/select';
import { fadeInAnimation } from '@/constants/animations';
import {
    BaseFormSectionProps,
    QueueDriverValue,
    QueueHandlers,
    QueueTypeValue,
    SelectChangeEvent,
} from '@/types';
import { AnimatePresence, motion } from 'motion/react';
import { useTranslation } from 'react-i18next';

type QueueDriverSelectorProps = Omit<BaseFormSectionProps, 'errors'> & {
    queueType: QueueTypeValue;
};

const QueueDriverSelector = ({
    data,
    setData,
    queueType,
}: QueueDriverSelectorProps) => {
    const { t } = useTranslation();

    const handleDriverChange = (e: SelectChangeEvent) => {
        setData('queue_driver', e.target.value as QueueDriverValue);
    };

    return (
        <Field>
            <Label>{t('laravel.queue_driver')}</Label>
            <Select
                name="queue_driver"
                value={data.queue_driver ?? ''}
                onChange={handleDriverChange}
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

type QueueConfigurationProps = BaseFormSectionProps & QueueHandlers;

export const QueueConfiguration = ({
    data,
    setData,
    errors,
    handleQueueChange,
}: QueueConfigurationProps) => {
    const { t } = useTranslation();

    const currentQueueType = data.queue_type;
    const showDriverSelector = currentQueueType && currentQueueType !== 'none';

    return (
        <>
            <Field>
                <Label>{t('laravel.queue_service')}</Label>
                <Select
                    name="queue_type"
                    value={currentQueueType ?? 'none'}
                    onChange={handleQueueChange}
                    required
                    invalid={!!errors.queue_type}
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
                        />
                    </motion.div>
                )}
            </AnimatePresence>
        </>
    );
};
