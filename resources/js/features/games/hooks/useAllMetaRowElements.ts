import { useMemo } from 'react';
import { route } from 'ziggy-js';

import { extractAndProcessHubMetadata } from '../utils/extractAndProcessHubMetadata';

export function useAllMetaRowElements(
  game: App.Platform.Data.Game,
  allGameHubs: App.Platform.Data.GameSet[],
) {
  const developerRowElements = useMemo(
    () =>
      buildMetaRowElements({
        hubs: allGameHubs,
        hubTitleIncludes: ['Developer -', 'Hacker -'],
        primaryLabel: 'Developer',
        altLabels: ['Hacker'],
        fallbackValue: game.developer,
      }),
    [allGameHubs, game.developer],
  );

  const publisherRowElements = useMemo(
    () =>
      buildMetaRowElements({
        hubs: allGameHubs,
        hubTitleIncludes: ['Publisher - ', 'Hacks -'],
        primaryLabel: 'Publisher',
        altLabels: ['Hacks'],
        fallbackValue: game.publisher,
      }),
    [allGameHubs, game.publisher],
  );

  const genreRowElements = useMemo(
    () =>
      buildMetaRowElements({
        hubs: allGameHubs,
        hubTitleIncludes: ['Genre -', 'Subgenre -'],
        primaryLabel: 'Genre',
        altLabels: ['Subgenre'],
        fallbackValue: game.genre,
      }),
    [allGameHubs, game.genre],
  );

  const languageRowElements = useMemo(
    () =>
      buildMetaRowElements({
        hubs: allGameHubs,
        hubTitleIncludes: ['Meta - Language -', 'Meta - Language Patch -'],
        primaryLabel: 'Meta - Language',
        altLabels: ['Meta - Language Patch'],
        altLabelsLast: true,
        markAltLabels: true,
      }),
    [allGameHubs],
  );

  const themeRowElements = useMemo(
    () =>
      buildMetaRowElements({
        hubs: allGameHubs,
        hubTitleIncludes: ['Theme -'],
        primaryLabel: 'Theme',
      }),
    [allGameHubs],
  );

  const perspectiveRowElements = useMemo(
    () =>
      buildMetaRowElements({
        hubs: allGameHubs,
        hubTitleIncludes: ['Perspective -'],
        primaryLabel: 'Perspective',
      }),
    [allGameHubs],
  );

  const featureRowElements = useMemo(
    () =>
      buildMetaRowElements({
        hubs: allGameHubs,
        hubTitleIncludes: ['Feature -', 'Game Mechanic -'],
        primaryLabel: 'Feature',
        altLabels: ['Game Mechanic'],
        altLabelsLast: false,
      }),
    [allGameHubs],
  );

  const creditRowElements = useMemo(
    () =>
      buildMetaRowElements({
        hubs: allGameHubs,
        hubTitleIncludes: ['Credits -'],
        primaryLabel: 'Credits',
      }),
    [allGameHubs],
  );

  const technicalRowElements = useMemo(
    () =>
      buildMetaRowElements({
        hubs: allGameHubs,
        hubTitleIncludes: ['Technical -'],
        primaryLabel: 'Technical',
      }),
    [allGameHubs],
  );

  const miscRowElements = useMemo(
    () =>
      buildMetaRowElements({
        hubs: allGameHubs,
        hubTitleIncludes: ['Misc. -'],
        primaryLabel: 'Misc.',
      }),
    [allGameHubs],
  );

  const protagonistRowElements = useMemo(
    () =>
      buildMetaRowElements({
        hubs: allGameHubs,
        hubTitleIncludes: ['Protagonist -'],
        primaryLabel: 'Protagonist',
      }),
    [allGameHubs],
  );

  const settingRowElements = useMemo(
    () =>
      buildMetaRowElements({
        hubs: allGameHubs,
        hubTitleIncludes: ['Setting -'],
        primaryLabel: 'Setting',
      }),
    [allGameHubs],
  );

  const regionalRowElements = useMemo(
    () =>
      buildMetaRowElements({
        hubs: allGameHubs,
        hubTitleIncludes: ['Regional -'],
        primaryLabel: 'Regional',
      }),
    [allGameHubs],
  );

  const raFeatureRowElements = useMemo(
    () =>
      buildMetaRowElements({
        hubs: allGameHubs,
        hubTitleIncludes: [
          'Meta -',
          'Rollout Sets -',
          'AotW -',
          'RANews -',
          'RA Awards -',
          'Custom Awards -',
          'Console Wars',
          'DevJam',
          'Challenge League',
        ],
        hubTitleExcludes: ['Meta - Language'],
        primaryLabel: 'Meta',
        altLabels: [
          'AotW',
          'Rollout Sets',
          'RANews',
          'RA Awards',
          'Custom Awards',
          'Console Wars',
          'DevJam',
          'Challenge League',
        ],
        keepPrefixFor: ['Rollout Sets', 'DevJam', 'Challenge League', 'Console Wars'],
      }),
    [allGameHubs],
  );

  const allUsedHubIds = useMemo(() => {
    const allRows = [
      ...developerRowElements,
      ...publisherRowElements,
      ...genreRowElements,
      ...languageRowElements,
      ...themeRowElements,
      ...perspectiveRowElements,
      ...featureRowElements,
      ...creditRowElements,
      ...technicalRowElements,
      ...miscRowElements,
      ...protagonistRowElements,
      ...settingRowElements,
      ...regionalRowElements,
      ...raFeatureRowElements,
    ];

    return [...new Set(allRows.map((row) => row.hubId).filter(Boolean))] as number[];
  }, [
    developerRowElements,
    publisherRowElements,
    genreRowElements,
    languageRowElements,
    themeRowElements,
    perspectiveRowElements,
    featureRowElements,
    creditRowElements,
    technicalRowElements,
    miscRowElements,
    protagonistRowElements,
    settingRowElements,
    regionalRowElements,
    raFeatureRowElements,
  ]);

  return {
    allUsedHubIds,
    developerRowElements,
    publisherRowElements,
    genreRowElements,
    languageRowElements,
    themeRowElements,
    perspectiveRowElements,
    featureRowElements,
    creditRowElements,
    technicalRowElements,
    miscRowElements,
    protagonistRowElements,
    settingRowElements,
    regionalRowElements,
    raFeatureRowElements,
  };
}

function buildMetaRowElements(props: {
  hubs: App.Platform.Data.GameSet[];
  primaryLabel: string;
  altLabels?: string[];
  hubTitleIncludes?: string[];
  hubTitleExcludes?: string[];
  altLabelsLast?: boolean;
  markAltLabels?: boolean;
  fallbackValue?: string;
  keepPrefixFor?: string[];
}): Array<{ label: string; hubId?: number; href?: string }> {
  const {
    hubs,
    hubTitleIncludes = [],
    hubTitleExcludes = [],
    primaryLabel,
    altLabels = [],
    altLabelsLast = false,
    markAltLabels = false,
    fallbackValue,
    keepPrefixFor = [],
  } = props;

  const metaItems = extractAndProcessHubMetadata(
    hubs,
    primaryLabel,
    altLabels,
    hubTitleIncludes,
    hubTitleExcludes,
    fallbackValue,
    altLabelsLast,
    markAltLabels,
    keepPrefixFor,
  );

  // Convert to the final format with hrefs.
  return metaItems.map(({ label, hubId }) => ({
    hubId,
    label,
    href: hubId ? route('hub.show', hubId) : undefined,
  }));
}
