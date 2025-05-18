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
        hubTitleIncludes: ['Publisher - '],
        primaryLabel: 'Publisher',
        fallbackValue: game.publisher,
      }),
    [allGameHubs, game.publisher],
  );

  const hackOfRowElements = useMemo(
    () =>
      buildMetaRowElements({
        hubs: allGameHubs,
        hubTitleIncludes: ['Hacks - '],
        primaryLabel: 'Hacks',
      }),
    [allGameHubs],
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

  const eventsRowElements = useMemo(
    () =>
      buildMetaRowElements({
        hubs: allGameHubs,
        hubTitleIncludes: ['AotW -', 'Console Wars'],
        primaryLabel: 'AotW',
        altLabels: ['Console Wars I'],
        keepPrefixFor: ['Console Wars I'],
      }),
    [allGameHubs],
  );

  const raFeatureRowElements = useMemo(
    () =>
      buildMetaRowElements({
        hubs: allGameHubs,
        hubTitleIncludes: ['Meta -', 'RANews -', 'Custom Awards -'],
        hubTitleExcludes: ['Meta - Language'],
        primaryLabel: 'Meta',
        altLabels: ['RANews', 'Custom Awards'],
      }),
    [allGameHubs],
  );

  const formatRowElements = useMemo(
    () =>
      buildMetaRowElements({
        hubs: allGameHubs,
        hubTitleIncludes: ['Format -'],
        primaryLabel: 'Format',
      }),
    [allGameHubs],
  );

  const allUsedHubIds = useMemo(() => {
    const allRows = [
      ...creditRowElements,
      ...developerRowElements,
      ...eventsRowElements,
      ...featureRowElements,
      ...formatRowElements,
      ...genreRowElements,
      ...hackOfRowElements,
      ...languageRowElements,
      ...miscRowElements,
      ...perspectiveRowElements,
      ...protagonistRowElements,
      ...publisherRowElements,
      ...raFeatureRowElements,
      ...regionalRowElements,
      ...settingRowElements,
      ...technicalRowElements,
      ...themeRowElements,
    ];

    return [...new Set(allRows.map((row) => row.hubId).filter(Boolean))] as number[];
  }, [
    creditRowElements,
    developerRowElements,
    eventsRowElements,
    featureRowElements,
    formatRowElements,
    genreRowElements,
    hackOfRowElements,
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
  ]);

  return {
    allUsedHubIds,
    creditRowElements,
    developerRowElements,
    eventsRowElements,
    featureRowElements,
    formatRowElements,
    genreRowElements,
    hackOfRowElements,
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
  };
}

function buildMetaRowElements(props: {
  hubs: App.Platform.Data.GameSet[];
  hubTitleIncludes: string[];
  primaryLabel: string;
  altLabels?: string[];
  hubTitleExcludes?: string[];
  altLabelsLast?: boolean;
  markAltLabels?: boolean;
  fallbackValue?: string;
  keepPrefixFor?: string[];
}): Array<{ label: string; hubId?: number; href?: string }> {
  const {
    hubs,
    hubTitleIncludes,
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
