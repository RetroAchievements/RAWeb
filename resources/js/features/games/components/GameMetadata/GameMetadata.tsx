import { type FC, useState } from 'react';
import { useTranslation } from 'react-i18next';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import {
  BaseTable,
  BaseTableBody,
  BaseTableCell,
  BaseTableRow,
} from '@/common/components/+vendor/BaseTable';
import { cn } from '@/common/utils/cn';
import { formatGameReleasedAt } from '@/common/utils/formatGameReleasedAt';

import type { useAllMetaRowElements } from '../../hooks/useAllMetaRowElements';
import { GameMetadataRow } from './GameMetadataRow';

interface GameMetadataProps {
  allMetaRowElements: ReturnType<typeof useAllMetaRowElements>;
  game: App.Platform.Data.Game;
}

export const GameMetadata: FC<GameMetadataProps> = ({ allMetaRowElements, game }) => {
  const { t } = useTranslation();

  const [isSeeMoreOpen, setIsSeeMoreOpen] = useState(false);

  const {
    creditRowElements,
    developerRowElements,
    featureRowElements,
    genreRowElements,
    languageRowElements,
    miscRowElements,
    perspectiveRowElements,
    protagonistRowElements,
    publisherRowElements,
    raFeatureRowElements,
    regionalRowElements,
    settingRowElements,
    technicalRowElements,
    themeRowElements,
  } = allMetaRowElements;

  return (
    <div className="rounded-lg bg-embed p-1 light:border light:border-neutral-200 light:bg-white">
      <BaseTable className="overflow-hidden rounded-lg text-2xs">
        <BaseTableBody>
          <GameMetadataRow
            rowHeading={t('metaDeveloper', { count: developerRowElements.length })}
            elements={developerRowElements}
          />
          <GameMetadataRow
            rowHeading={t('metaPublisher', { count: publisherRowElements.length })}
            elements={publisherRowElements}
          />
          <GameMetadataRow
            rowHeading={t('metaGenre', { count: genreRowElements.length })}
            elements={genreRowElements}
          />

          {game.releasedAt ? (
            <GameMetadataRow
              rowHeading={t('Released')}
              elements={[
                {
                  label: formatGameReleasedAt(
                    game.releasedAt,
                    game.releasedAtGranularity,
                  ) as string,
                },
              ]}
            />
          ) : null}

          <GameMetadataRow
            rowHeading={t('metaLanguage', { count: languageRowElements.length })}
            elements={languageRowElements}
          />
          <GameMetadataRow
            rowHeading={t('metaFeature', { count: featureRowElements.length })}
            elements={featureRowElements}
          />
          <GameMetadataRow
            rowHeading={t('metaCredit', { count: creditRowElements.length })}
            elements={creditRowElements}
          />
          <GameMetadataRow
            rowHeading={t('metaPerspective', { count: perspectiveRowElements.length })}
            elements={perspectiveRowElements}
          />

          {!isSeeMoreOpen ? (
            <BaseTableRow className="do-not-highlight">
              <BaseTableCell colSpan={2} className="p-0">
                <div className="flex w-full justify-center">
                  <BaseButton
                    size="sm"
                    className={cn(
                      'w-full rounded-t-none border-none bg-transparent !text-2xs',
                      'lg:transition-none lg:active:translate-y-0 lg:active:scale-100',
                    )}
                    onClick={() => setIsSeeMoreOpen(true)}
                  >
                    {t('See more')}
                  </BaseButton>
                </div>
              </BaseTableCell>
            </BaseTableRow>
          ) : null}

          {isSeeMoreOpen ? (
            <>
              <GameMetadataRow
                rowHeading={t('metaProtagonist', { count: protagonistRowElements.length })}
                elements={protagonistRowElements}
              />
              <GameMetadataRow
                rowHeading={t('metaTheme', { count: themeRowElements.length })}
                elements={themeRowElements}
              />
              <GameMetadataRow
                rowHeading={t('metaSetting', { count: settingRowElements.length })}
                elements={settingRowElements}
              />

              <GameMetadataRow rowHeading={t('metaTechnical')} elements={technicalRowElements} />
              <GameMetadataRow rowHeading={t('metaRegional')} elements={regionalRowElements} />
              <GameMetadataRow rowHeading={t('metaMisc')} elements={miscRowElements} />
              <GameMetadataRow rowHeading={t('metaRaFeature')} elements={raFeatureRowElements} />
            </>
          ) : null}
        </BaseTableBody>
      </BaseTable>
    </div>
  );
};
