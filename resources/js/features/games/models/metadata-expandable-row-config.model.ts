export interface MetadataExpandableRowConfig {
  elements: Array<{ label: string; hubId?: number; href?: string }>;
  key: string;

  countInHeading?: boolean;
  useListSeparators?: boolean;
}
