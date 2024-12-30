import { type FC, useState } from 'react';
import { Trans, useTranslation } from 'react-i18next';
import { LuAlertCircle } from 'react-icons/lu';

import {
  BaseAlert,
  BaseAlertDescription,
  BaseAlertTitle,
} from '@/common/components/+vendor/BaseAlert';
import { InertiaLink } from '@/common/components/InertiaLink';
import { buildTrackingClassNames } from '@/common/utils/buildTrackingClassNames';

export const GuestWelcomeCta: FC = () => {
  const { t } = useTranslation();

  return (
    <BaseAlert variant="notice" className="bg-embed light:bg-neutral-50">
      <LuAlertCircle className="size-5" />
      <BaseAlertTitle className="mb-3 text-xl font-semibold leading-4">
        {t('Welcome!')}
      </BaseAlertTitle>

      <BaseAlertDescription>
        <div className="flex flex-col gap-2">
          <div>
            <p>
              {t(
                'Build your profile, track your progress, and compete with friends on your favorite classic games.',
              )}
            </p>

            <p>
              <Trans
                i18nKey="We provide <1>the emulators</1>, you just need <2>the games</2>. From Atari 2600 to PlayStation 2, and everything in between."
                components={{
                  1: (
                    <a
                      href={route('download.index')}
                      className={buildTrackingClassNames('Click Guest Download Link')}
                    />
                  ),
                  2: (
                    <InertiaLink
                      href={route('game.index')}
                      className={buildTrackingClassNames('Click Guest All Games Link')}
                    />
                  ),
                }}
              />
            </p>
          </div>

          <div className="mt-2">
            <RandomGameLink />
          </div>
        </div>
      </BaseAlertDescription>
    </BaseAlert>
  );
};

const RandomGameLink: FC = () => {
  const { t } = useTranslation();

  const randomGameIds = [
    1, // Sonic the Hedgehog (Mega Drive)
    3, // Streets of Rage 2 (Mega Drive)
    1451, // Mega Man 2 (NES)
    1995, // Super Mario Bros. 3 (NES)
    335, // Legend of Zelda, The: A Link to the Past (SNES)
    446, // Donkey Kong Country 2: Diddy's Kong Quest (SNES)
    724, // Pokemon Red | Pokemon Blue (GB/C)
    5371, // Legend of Zelda, The: Link's Awakening DX (GB/C)
    10078, // Mario Kart 64 (N64)
    10210, // Banjo-Kazooie (N64)
    10434, // Crash Bandicoot (PSX)
    11191, // Pitfall! (Atari 2600)
    2831, // Metal Gear Solid 3: Subsistence (PS2)
    2721, // Dragon Quest VIII: Journey of the Cursed King (PS2)
  ];

  const [randomGameId] = useState(() => {
    return randomGameIds[Math.floor(Math.random() * randomGameIds.length)];
  });

  return (
    <a
      href={route('game.show', { game: randomGameId })}
      className={buildTrackingClassNames('Click Guest Random Game Page Link')}
    >
      {t('Which of these achievements do you think you can get?')}
    </a>
  );
};
