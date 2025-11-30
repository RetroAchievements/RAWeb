import type { FC } from 'react';
import { Trans, useTranslation } from 'react-i18next';

import { usePageProps } from '@/common/hooks/usePageProps';

export const RedirectRoot: FC = () => {
  const { url } = usePageProps<App.Community.Data.RedirectPagePropsData>();
  const { t } = useTranslation();

  return (
    <div className="grid gap-y-6">
      <div className="w-full rounded bg-gradient-to-b from-amber-400 to-yellow-700 p-2">
        <div className="flex flex-col items-center gap-y-2 md:flex-row md:gap-x-6">
          <img
            src="/assets/images/cheevo/popcorn.webp"
            alt="cheevo eating popcorn"
            className="h-24 w-24"
          />

          <div className="text-center md:flex md:flex-col md:gap-y-1 md:text-left">
            <p className="text-white md:text-base">{t('Heads up!')}</p>
            <h1 className="mb-0 border-0 text-base text-white md:text-lg md:font-bold">
              {t('You are leaving RetroAchievements.')}
            </h1>
          </div>
        </div>
      </div>

      <div className="rounded border border-embed-highlight bg-embed p-4 md:text-center">
        <div className="flex flex-col gap-y-4">
          <p>
            <Trans
              i18nKey="<1>{{url}}</1> is not part of RetroAchievements. We don't know what you might see there."
              values={{ url }}
              components={{ 1: <span className="font-bold" /> }}
            />
          </p>

          <div className="flex w-full justify-center">
            <a
              href={url}
              onClick={(e) => {
                e.preventDefault();
                window.location.replace(url);
              }}
              rel="noreferrer"
              className="btn"
            >
              {t('Continue to external site')}
            </a>
          </div>
        </div>
      </div>
    </div>
  );
};
