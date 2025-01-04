import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { formatDate } from '@/common/utils/l10n/formatDate';

interface UnlockedAtLabelProps {
    when: string,
}

export const UnlockedAtLabel: FC<UnlockedAtLabelProps> = ({ when }) => {
    const { t } = useTranslation();

    return (
        <span className="flex items-center gap-0.5 text-2xs text-neutral-500">
            {t('Unlocked {{when}}', { when: formatDate(when, 'lll') })}
        </span>
    );
};