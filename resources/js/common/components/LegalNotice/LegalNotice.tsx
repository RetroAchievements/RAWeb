import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

export const LegalNotice: FC = () => {
  const { t } = useTranslation();

  return (
    <div>
      <h3 className="mb-0 border-b-0 text-lg font-medium">{t('Disclaimer about ROMs')}</h3>

      <p className="font-bold">
        {t(
          'RetroAchievements.org does not condone or supply any copyright-protected ROMs to be used in conjunction with the supplied emulators.',
        )}
      </p>

      <p>
        {t(
          'There are no copyright-protected ROMs available for download on RetroAchievements.org.',
        )}
      </p>
    </div>
  );
};
