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

import type { useAllMetaRowElements } from '../../hooks/useAllMetaRowElements';
import type { MetadataExpandableRowConfig } from '../../models';
import { getNonCanonicalTitles } from '../../utils/getNonCanonicalTitles';
import { GameMetadataRow } from './GameMetadataRow';
import { GameOtherNamesRow } from './GameOtherNamesRow';
import { GameReleaseDatesRow } from './GameReleaseDatesRow';

interface GameMetadataProps {
  allMetaRowElements: ReturnType<typeof useAllMetaRowElements>;
  game: App.Platform.Data.Game;
}

export const GameMetadata: FC<GameMetadataProps> = ({ allMetaRowElements, game }) => {
  const { t } = useTranslation();

  const {
    creditRowElements,
    developerRowElements,
    featureRowElements,
    formatRowElements,
    genreRowElements,
    hackOfRowElements,
    languageRowElements,
    miscRowElements,
    perspectiveRowElements,
    protagonistRowElements,
    publisherRowElements,
    regionalRowElements,
    settingRowElements,
    technicalRowElements,
    themeRowElements,
  } = allMetaRowElements;

  // These rows are buried under a "See more" button.
  const seeMoreRows: MetadataExpandableRowConfig[] = [
    {
      key: 'protagonist',
      elements: protagonistRowElements,
      countInHeading: true,
      useListSeparators: true,
    },
    { key: 'theme', elements: themeRowElements, countInHeading: true },
    { key: 'setting', elements: settingRowElements, countInHeading: true },
    { key: 'format', elements: formatRowElements },
    { key: 'technical', elements: technicalRowElements },
    { key: 'regional', elements: regionalRowElements },
    { key: 'misc', elements: miscRowElements, useListSeparators: false },
  ];

  const seeMoreRowsCount = seeMoreRows.filter((row) => row.elements?.length > 0).length;
  const canShowSeeMoreSection = seeMoreRowsCount > 0;

  const [isSeeMoreOpen, setIsSeeMoreOpen] = useState(seeMoreRowsCount <= 2);

  const canShowPublisherRow =
    publisherRowElements.length > 0 &&
    (hackOfRowElements.length === 0 ||
      !publisherRowElements.every((el) => el.label.includes('Hack -')));

  const gameReleasesWithDates = game.releases?.filter((r) => r.releasedAt);
  const allNonCanonicalTitles = getNonCanonicalTitles(game.releases);

  return (
    <div className="rounded-lg bg-embed p-1 light:border light:border-neutral-200 light:bg-white">
      <BaseTable className="overflow-hidden rounded-lg text-2xs">
        <BaseTableBody>
          <GameMetadataRow
            rowHeading={t('metaDeveloper', { count: developerRowElements.length })}
            elements={developerRowElements}
          />

          {canShowPublisherRow ? (
            <GameMetadataRow
              rowHeading={t('metaPublisher', { count: publisherRowElements.length })}
              elements={publisherRowElements}
            />
          ) : null}

          <GameMetadataRow rowHeading={t('metaHackOf')} elements={hackOfRowElements} />
          <GameMetadataRow
            rowHeading={t('metaGenre', { count: genreRowElements.length })}
            elements={genreRowElements}
          />

          {gameReleasesWithDates?.length ? (
            <GameReleaseDatesRow releases={gameReleasesWithDates} />
          ) : null}

          {allNonCanonicalTitles?.length ? (
            <GameOtherNamesRow nonCanonicalTitles={allNonCanonicalTitles} />
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

          {canShowSeeMoreSection && !isSeeMoreOpen ? (
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
              {seeMoreRows.map((row) =>
                row.elements?.length > 0 ? (
                  <GameMetadataRow
                    key={`meta-${row.key}`}
                    rowHeading={t(
                      // eslint-disable-next-line @typescript-eslint/no-explicit-any -- intentional
                      `meta${row.key.charAt(0).toUpperCase()}${row.key.slice(1)}` as any,
                      row.countInHeading ? { count: row.elements.length } : undefined,
                    )}
                    elements={row.elements}
                    areListSeparatorsEnabled={row.useListSeparators}
                  />
                ) : null,
              )}
            </>
          ) : null}
        </BaseTableBody>
      </BaseTable>
    </div>
  );
};
