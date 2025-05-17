/**
 * Processes game hub metadata to extract standardized labels and IDs. Used to normalize
 * hub titles that follow patterns like "[Category - Label]" into consistent formats
 * for a sidebar metadata display component.
 *
 * @see GameMetadata.tsx
 * @see useAllMetaRowElements.ts
 *
 * - Extracts labels from hub titles matching specified patterns (eg: "[Primary - My Hub]").
 * - Supports primary and alternative label categories with optional marking (*).
 * - Can preserve certain prefixes in the final label.
 * - Provides fallback values when no matching hubs are found.
 * - Handles special case normalization (e.g. "Hacks" to "Hack").
 */
export function extractAndProcessHubMetadata(
  hubs: App.Platform.Data.GameSet[],
  primaryLabel: string,
  altLabels: string[],
  hubPatterns: string[],
  excludePatterns: string[],
  fallbackValue?: string,
  altLabelsLast = false,
  markAltLabels = false,
  keepPrefixFor: string[] = [],
): Array<{ label: string; hubId?: number }> {
  // Stores unique labels with their metadata. Using a Map ensures we don't duplicate labels
  // and allows us to prefer hubs with IDs over those without.
  const metaMap = new Map<string, { label: string; hubId?: number; isAlt?: boolean }>();

  // Start with any provided fallback values.
  if (fallbackValue) {
    for (const value of fallbackValue.split(',').map((v) => v.trim())) {
      metaMap.set(value, { label: value });
    }
  }

  if (hubPatterns.length > 0) {
    // Filter hubs to only those matching our include patterns but not exclude patterns.
    const filteredHubs = hubs.filter((hub) => {
      const title = hub.title?.toLowerCase() || '';

      return (
        hubPatterns.some((pattern) => title.includes(pattern.toLowerCase())) &&
        !excludePatterns.some((pattern) => title.includes(pattern.toLowerCase()))
      );
    });

    // Build prefix patterns like "[Primary - " and "[Alt - " to match against hub titles.
    const allLabels = [primaryLabel, ...altLabels];
    const hubPrefixes = allLabels.map((l) => `[${l} - `);

    for (const hub of filteredHubs) {
      const title = hub.title!;
      const prefix = hubPrefixes.find((p) => title.startsWith(p));

      if (prefix) {
        // Extract the actual label by removing the prefix and trailing bracket.
        let value = title.slice(prefix.length, -1);
        const isAlt = altLabels.some((altLabel) => prefix === `[${altLabel} - `);

        // Normalize "Hacks" to "Hack" for consistency in series titles.
        if (prefix.startsWith('[Hack - ')) {
          value = value.replace('Hacks - ', 'Hack - ');
        }

        // Some labels should retain their category prefix (eg: "Rollout Sets - PlayStation").
        const shouldKeepPrefix = keepPrefixFor.some((prefixToKeep) => {
          const labelPart = prefix.substring(1, prefix.length - 3);

          return prefixToKeep.startsWith(labelPart);
        });

        if (shouldKeepPrefix) {
          const prefixLabel = prefix.substring(1, prefix.length - 3);
          value = `${prefixLabel} - ${value}`;
        }

        if (isAlt && markAltLabels) {
          value = `${value}*`;
        }

        const existing = metaMap.get(value);
        if (!existing || (!existing.hubId && hub.id)) {
          metaMap.set(value, { label: value, hubId: hub.id, isAlt });
        }
      }
    }
  }

  const result = Array.from(metaMap.values());

  // Sort the results, optionally grouping alternative labels after primary ones.
  if (altLabelsLast) {
    result.sort((a, b) => {
      if (a.isAlt !== b.isAlt) return a.isAlt ? 1 : -1;

      return a.label.localeCompare(b.label);
    });
  } else {
    result.sort((a, b) => a.label.localeCompare(b.label));
  }

  return result.map(({ label, hubId }) => ({ label, hubId }));
}
