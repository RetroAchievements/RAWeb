import type { Table } from '@tanstack/react-table';
import { useTranslation } from 'react-i18next';
import { LuCircleDot, LuMedal, LuSparkles, LuTrophy } from 'react-icons/lu';

import { usePageProps } from '@/common/hooks/usePageProps';

import type { FilterOptions } from '../../../DataTableFacetedFilter';
import { DataTableFacetedFilter } from '../../../DataTableFacetedFilter';

interface DataTableProgressFilterProps<TData> {
  table: Table<TData>;

  variant?: 'base' | 'drawer';
}
export function DataTableProgressFilter<TData>({
  table,
  variant = 'base',
}: DataTableProgressFilterProps<TData>) {
  const { auth } = usePageProps();

  const { t } = useTranslation();

  const playerPreferredProgressFilterOptions = usePlayerPreferredProgressFilterOptions();

  return (
    <DataTableFacetedFilter
      className="w-full sm:w-auto"
      baseCommandListClassName="!max-h-[calc(80vh-100px)]"
      column={table.getColumn('progress')}
      t_title={t('Progress')}
      options={
        [
          {
            options: [{ t_label: t('All Games'), isDefaultOption: true }],
          },
          ...playerPreferredProgressFilterOptions,
        ] as FilterOptions
      }
      isSingleSelect={true}
      isSearchable={false}
      variant={variant}
      disabled={!auth?.user}
    />
  );
}

function usePlayerPreferredProgressFilterOptions(): FilterOptions<App.Platform.Enums.GameListProgressFilterValue> {
  const { auth } = usePageProps();

  const { t } = useTranslation();

  const baseOptions: FilterOptions<App.Platform.Enums.GameListProgressFilterValue> = [
    { t_label: t('None'), value: 'unstarted', icon: LuSparkles },
    { t_label: t('Some (No Awards)'), value: 'unfinished', icon: LuSparkles },
  ];

  const playerPreferredMode = auth?.user.playerPreferredMode ?? 'hardcore';

  if (playerPreferredMode === 'hardcore') {
    return [
      {
        t_heading: t('Progress'),
        options: baseOptions,
      },
      {
        t_heading: t('Awards - At Least'),
        options: [
          {
            t_label: t('Beaten or Higher'),
            value: 'gte_beaten_hardcore',
            icon: LuCircleDot,
          },
        ],
      },
      {
        t_heading: t('Awards - Exact'),
        options: [
          { t_label: t('Beaten Only'), value: 'eq_beaten_hardcore', icon: LuCircleDot },
          { t_label: t('Mastered Only'), value: 'eq_mastered', icon: LuTrophy },
        ],
      },
      {
        t_heading: t('Special Filters'),
        options: [
          {
            t_label: t('Missing New Achievements'),
            t_description: t('Mastered games with unearned achievements from a revision'),
            value: 'revised',
            icon: LuMedal,
          },
          {
            t_label: t('Not Yet Mastered'),
            t_description: t("Exclude games you've already mastered"),
            value: 'neq_mastered',
            icon: LuMedal,
          },
        ],
      },
    ];
  }

  if (playerPreferredMode === 'softcore') {
    return [
      {
        t_heading: t('Progress'),
        options: baseOptions,
      },
      {
        t_heading: t('Awards - At Least'),
        options: [
          {
            t_label: t('Beaten (softcore) or Higher'),
            value: 'gte_beaten_softcore',
            icon: LuCircleDot,
          },
        ],
      },
      {
        t_heading: t('Awards - Exact'),
        options: [
          { t_label: t('Beaten (softcore) Only'), value: 'eq_beaten_softcore', icon: LuCircleDot },
          { t_label: t('Completed Only'), value: 'eq_completed', icon: LuTrophy },
        ],
      },
      {
        t_heading: t('Special Filters'),
        options: [
          {
            t_label: t('Missing New Achievements'),
            t_description: t('Completed games with unearned achievements from a revision'),
            value: 'revised',
            icon: LuMedal,
          },
          {
            t_label: t('Not Yet Completed'),
            t_description: t("Exclude games you've already completed"),
            value: 'neq_mastered',
            icon: LuMedal,
          },
        ],
      },
    ];
  }

  // Mixed mode.
  return [
    {
      t_heading: t('Progress'),
      options: baseOptions,
    },
    {
      t_heading: t('Awards - At Least'),
      options: [
        {
          t_label: t('Beaten (Softcore) or Higher'),
          value: 'gte_beaten_softcore',
          icon: LuCircleDot,
        },
        {
          t_label: t('Beaten (Hardcore) or Higher'),
          value: 'gte_beaten_hardcore',
          icon: LuCircleDot,
        },
        { t_label: t('Completed or Higher'), value: 'gte_completed', icon: LuTrophy },
      ],
    },
    {
      t_heading: t('Awards - Exact'),
      options: [
        { t_label: t('Beaten (Softcore) Only'), value: 'eq_beaten_softcore', icon: LuCircleDot },
        { t_label: t('Beaten (Hardcore) Only'), value: 'eq_beaten_hardcore', icon: LuCircleDot },
        { t_label: t('Completed Only'), value: 'eq_completed', icon: LuTrophy },
        { t_label: t('Mastered Only'), value: 'eq_mastered', icon: LuTrophy },
      ],
    },
    {
      t_heading: t('Special Filters'),
      options: [
        {
          t_label: t('Missing New Achievements'),
          t_description: t('Completed/mastered games with unearned achievements from a revision'),
          value: 'revised',
          icon: LuMedal,
        },
        {
          t_label: t('Not Yet Completed/Mastered'),
          t_description: t("Exclude games you've already completed/mastered"),
          value: 'neq_mastered',
          icon: LuMedal,
        },
      ],
    },
  ];
}
