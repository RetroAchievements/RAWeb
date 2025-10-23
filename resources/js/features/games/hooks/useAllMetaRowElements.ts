import { useMemo } from 'react';
import { route } from 'ziggy-js';

import { extractAndProcessHubMetadata } from '../utils/extractAndProcessHubMetadata';
import { hubIds } from '../utils/hubIds';

export function useAllMetaRowElements(
  game: App.Platform.Data.Game,
  allGameHubs: App.Platform.Data.GameSet[],
) {
  const filteredHubs = useMemo(
    () =>
      allGameHubs.filter((hub) => hub.id !== hubIds.mature && hub.id !== hubIds.epilepsyWarning),
    [allGameHubs],
  );

  const developerRowElements = useMemo(
    () =>
      buildMetaRowElements({
        hubs: filteredHubs,
        hubTitleIncludes: ['Developer -', 'Hacker -'],
        primaryLabel: 'Developer',
        altLabels: ['Hacker'],
        fallbackValue: game.developer,
      }),
    [filteredHubs, game.developer],
  );

  const publisherRowElements = useMemo(
    () =>
      buildMetaRowElements({
        hubs: filteredHubs,
        hubTitleIncludes: ['Publisher - '],
        primaryLabel: 'Publisher',
        fallbackValue: game.publisher,
      }),
    [filteredHubs, game.publisher],
  );

  const hackOfRowElements = useMemo(
    () =>
      buildMetaRowElements({
        hubs: filteredHubs,
        hubTitleIncludes: ['Hacks - '],
        primaryLabel: 'Hacks',
      }),
    [filteredHubs],
  );

  const genreRowElements = useMemo(
    () =>
      buildMetaRowElements({
        hubs: filteredHubs,
        hubTitleIncludes: ['Genre -', 'Subgenre -'],
        primaryLabel: 'Genre',
        altLabels: ['Subgenre'],
        fallbackValue: game.genre,
      }),
    [filteredHubs, game.genre],
  );

  const languageRowElements = useMemo(
    () =>
      buildMetaRowElements({
        hubs: filteredHubs,
        hubTitleIncludes: ['Meta - Language -', 'Meta - Language Patch -'],
        primaryLabel: 'Meta - Language',
        altLabels: ['Meta - Language Patch'],
        altLabelsLast: true,
        markAltLabels: true,
      }),
    [filteredHubs],
  );

  const themeRowElements = useMemo(
    () =>
      buildMetaRowElements({
        hubs: filteredHubs,
        hubTitleIncludes: ['Theme -'],
        primaryLabel: 'Theme',
      }),
    [filteredHubs],
  );

  const perspectiveRowElements = useMemo(
    () =>
      buildMetaRowElements({
        hubs: filteredHubs,
        hubTitleIncludes: ['Perspective -'],
        primaryLabel: 'Perspective',
      }),
    [filteredHubs],
  );

  const featureRowElements = useMemo(
    () =>
      buildMetaRowElements({
        hubs: filteredHubs,
        hubTitleIncludes: ['Feature -', 'Game Mechanic -'],
        primaryLabel: 'Feature',
        altLabels: ['Game Mechanic'],
        altLabelsLast: false,
      }),
    [filteredHubs],
  );

  const creditRowElements = useMemo(
    () =>
      buildMetaRowElements({
        hubs: filteredHubs,
        hubTitleIncludes: ['Credits -'],
        primaryLabel: 'Credits',
      }),
    [filteredHubs],
  );

  const technicalRowElements = useMemo(
    () =>
      buildMetaRowElements({
        hubs: filteredHubs,
        hubTitleIncludes: ['Technical -'],
        primaryLabel: 'Technical',
      }),
    [filteredHubs],
  );

  const protagonistRowElements = useMemo(
    () =>
      buildMetaRowElements({
        hubs: filteredHubs,
        hubTitleIncludes: ['Protagonist -'],
        primaryLabel: 'Protagonist',
      }),
    [filteredHubs],
  );

  const settingRowElements = useMemo(
    () =>
      buildMetaRowElements({
        hubs: filteredHubs,
        hubTitleIncludes: ['Setting -'],
        primaryLabel: 'Setting',
      }),
    [filteredHubs],
  );

  const regionalRowElements = useMemo(
    () =>
      buildMetaRowElements({
        hubs: filteredHubs,
        hubTitleIncludes: ['Regional -'],
        primaryLabel: 'Regional',
      }),
    [filteredHubs],
  );

  const formatRowElements = useMemo(
    () =>
      buildMetaRowElements({
        hubs: filteredHubs,
        hubTitleIncludes: ['Format -'],
        primaryLabel: 'Format',
      }),
    [filteredHubs],
  );

  const allUsedHubIds = useMemo(() => {
    const allRows = [
      ...creditRowElements,
      ...developerRowElements,
      ...featureRowElements,
      ...formatRowElements,
      ...genreRowElements,
      ...hackOfRowElements,
      ...languageRowElements,
      ...perspectiveRowElements,
      ...protagonistRowElements,
      ...publisherRowElements,
      ...regionalRowElements,
      ...settingRowElements,
      ...technicalRowElements,
      ...themeRowElements,
    ];

    return [...new Set(allRows.map((row) => row.hubId).filter(Boolean))] as number[];
  }, [
    creditRowElements,
    developerRowElements,
    featureRowElements,
    formatRowElements,
    genreRowElements,
    hackOfRowElements,
    languageRowElements,
    perspectiveRowElements,
    protagonistRowElements,
    publisherRowElements,
    regionalRowElements,
    settingRowElements,
    technicalRowElements,
    themeRowElements,
  ]);

  return {
    allUsedHubIds,
    creditRowElements,
    developerRowElements,
    featureRowElements,
    formatRowElements,
    genreRowElements,
    hackOfRowElements,
    languageRowElements,
    perspectiveRowElements,
    protagonistRowElements,
    publisherRowElements,
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
