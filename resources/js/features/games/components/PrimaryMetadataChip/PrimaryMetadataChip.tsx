import { type FC, Fragment } from 'react';
import type { IconType } from 'react-icons/lib';
import { route } from 'ziggy-js';

import { BaseChip } from '@/common/components/+vendor/BaseChip';
import type { TranslatedString } from '@/types/i18next';

interface PrimaryMetadataChipProps {
  hubAltLabels: string[];
  hubs: App.Platform.Data.GameSet[];
  hubLabel: string;
  Icon: IconType;
  visibleLabel: TranslatedString;

  metadataValue?: string;
}

export const PrimaryMetadataChip: FC<PrimaryMetadataChipProps> = ({
  hubAltLabels,
  hubs,
  Icon,
  hubLabel,
  metadataValue,
  visibleLabel,
}) => {
  // Filter hubs that match the label or any altLabels.
  const attachedHubs = hubs.filter((h) => {
    const title = h.title?.toLowerCase();

    return (
      title?.includes(`${hubLabel.toLowerCase()} -`) ||
      hubAltLabels.some((alt) => title?.includes(`${alt.toLowerCase()} -`))
    );
  });

  // If there's no metadata value and no relevant hubs, return null.
  if (!metadataValue && !attachedHubs.length) {
    return null;
  }

  const metaList = buildMetaList(hubLabel, metadataValue, attachedHubs, hubAltLabels);

  return (
    <BaseChip data-testid="game-meta">
      <Icon className="size-4" />

      <span>
        {visibleLabel}
        {':'}
      </span>
      <span className="inline">
        {metaList.map((item, index) => (
          <Fragment key={`${hubLabel.toLowerCase()}-${item.label}`}>
            {item.hubId ? (
              <a href={route('hub.show', { gameSet: item.hubId })}>{item.label}</a>
            ) : (
              <span>{item.label}</span>
            )}
            {index !== metaList.length - 1 ? <span>{', '}</span> : null}
          </Fragment>
        ))}
      </span>
    </BaseChip>
  );
};

function buildMetaList(
  label: string,
  metadataValue: string | undefined,
  attachedHubs: App.Platform.Data.GameSet[],
  altLabels: string[],
): Array<{ label: string; hubId?: number }> {
  // Split metadataValue into individual values.
  const metadataValues = metadataValue ? metadataValue.split(',').map((v) => v.trim()) : [];

  // Create initial list from metadata values.
  const initialMetaList: Array<{ label: string; hubId?: number }> = metadataValues.map((value) => ({
    label: value,
  }));

  // Add hub-derived values.
  const hubPrefixes = [label, ...altLabels].map((l) => `[${l} - `);
  for (const hub of attachedHubs) {
    const title = hub.title!;
    const prefix = hubPrefixes.find((p) => title.startsWith(p));
    if (prefix) {
      let value = title.slice(prefix.length, -1); // Remove prefix and trailing ].

      // Special handling for "Hack" prefix.
      if (prefix.startsWith('[Hack - ')) {
        value = value.replace('Hacks - ', 'Hack - ');
      }

      initialMetaList.push({ label: value, hubId: hub.id });
    }
  }

  // Deduplicate by label, favoring entries with hubId.
  const metaMap = new Map<string, { label: string; hubId?: number }>();
  for (const item of initialMetaList) {
    const existing = metaMap.get(item.label);

    if (!existing || (!existing.hubId && item.hubId)) {
      metaMap.set(item.label, item);
    }
  }

  return Array.from(metaMap.values());
}
