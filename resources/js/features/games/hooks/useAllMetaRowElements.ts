import { route } from 'ziggy-js';

import { buildMiscRowElements } from '../utils/buildMiscRowElements';
import { extractAndProcessHubMetadata } from '../utils/extractAndProcessHubMetadata';
import { hubIds } from '../utils/hubIds';

export function useAllMetaRowElements(
  game: App.Platform.Data.Game,
  allGameHubs: App.Platform.Data.GameSet[],
) {
  const filteredHubs = allGameHubs.filter(
    (hub) => hub.id !== hubIds.mature && hub.id !== hubIds.epilepsyWarning,
  );

  const developerRowElements = buildMetaRowElements({
    hubs: filteredHubs,
    hubTitleIncludes: ['Developer -', 'Hacker -'],
    primaryLabel: 'Developer',
    altLabels: ['Hacker'],
    fallbackValue: game.developer,
  });

  const publisherRowElements = buildMetaRowElements({
    hubs: filteredHubs,
    hubTitleIncludes: ['Publisher - '],
    primaryLabel: 'Publisher',
    fallbackValue: game.publisher,
  });

  const hackOfRowElements = buildMetaRowElements({
    hubs: filteredHubs,
    hubTitleIncludes: ['Hacks - '],
    primaryLabel: 'Hacks',
  });

  const genreRowElements = buildMetaRowElements({
    hubs: filteredHubs,
    hubTitleIncludes: ['Genre -', 'Subgenre -'],
    primaryLabel: 'Genre',
    altLabels: ['Subgenre'],
    fallbackValue: game.genre,
  });

  const languageRowElements = buildMetaRowElements({
    hubs: filteredHubs,
    hubTitleIncludes: ['Meta - Language -', 'Meta - Language Patch -'],
    primaryLabel: 'Meta - Language',
    altLabels: ['Meta - Language Patch'],
    altLabelsLast: true,
    markAltLabels: true,
  });

  const themeRowElements = buildMetaRowElements({
    hubs: filteredHubs,
    hubTitleIncludes: ['Theme -'],
    primaryLabel: 'Theme',
  });

  const perspectiveRowElements = buildMetaRowElements({
    hubs: filteredHubs,
    hubTitleIncludes: ['Perspective -'],
    primaryLabel: 'Perspective',
  });

  const featureRowElements = buildMetaRowElements({
    hubs: filteredHubs,
    hubTitleIncludes: ['Feature -', 'Game Mechanic -'],
    primaryLabel: 'Feature',
    altLabels: ['Game Mechanic'],
    altLabelsLast: false,
  });

  const creditRowElements = buildMetaRowElements({
    hubs: filteredHubs,
    hubTitleIncludes: ['Credits -'],
    primaryLabel: 'Credits',
  });

  const technicalRowElements = buildMetaRowElements({
    hubs: filteredHubs,
    hubTitleIncludes: ['Technical -'],
    primaryLabel: 'Technical',
  });

  const protagonistRowElements = buildMetaRowElements({
    hubs: filteredHubs,
    hubTitleIncludes: ['Protagonist -'],
    primaryLabel: 'Protagonist',
  });

  const settingRowElements = buildMetaRowElements({
    hubs: filteredHubs,
    hubTitleIncludes: ['Setting -'],
    primaryLabel: 'Setting',
  });

  const regionalRowElements = buildMetaRowElements({
    hubs: filteredHubs,
    hubTitleIncludes: ['Regional -'],
    primaryLabel: 'Regional',
  });

  const formatRowElements = buildMetaRowElements({
    hubs: filteredHubs,
    hubTitleIncludes: ['Format -'],
    primaryLabel: 'Format',
  });

  const raFeatureRowElements = buildMetaRowElements({
    hubs: filteredHubs,
    hubTitleIncludes: ['RANews -', 'Custom Awards -'],
    primaryLabel: 'RANews',
    altLabels: ['Custom Awards'],
  });

  const usedHubIdsFromOtherCategories = new Set(
    [
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
      ...raFeatureRowElements,
      ...regionalRowElements,
      ...settingRowElements,
      ...technicalRowElements,
      ...themeRowElements,
    ]
      .map((row) => row.hubId)
      .filter((id): id is number => Boolean(id)),
  );

  const miscRowElements = buildMiscRowElements(filteredHubs, usedHubIdsFromOtherCategories, {
    keepPrefixFor: ['Clones', 'Fangames', 'Series Hacks', 'Unlicensed Games'],
  });

  const allUsedHubIds = [
    ...new Set(
      [
        ...creditRowElements,
        ...developerRowElements,
        ...featureRowElements,
        ...formatRowElements,
        ...genreRowElements,
        ...hackOfRowElements,
        ...languageRowElements,
        ...miscRowElements,
        ...perspectiveRowElements,
        ...protagonistRowElements,
        ...publisherRowElements,
        ...regionalRowElements,
        ...settingRowElements,
        ...technicalRowElements,
        ...themeRowElements,

        // Don't include raFeatureRowElements - those show in the sidebar Additional Hubs, not metadata.
      ]
        .map((row) => row.hubId)
        .filter(Boolean),
    ),
  ] as number[];

  return {
    allUsedHubIds,
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
