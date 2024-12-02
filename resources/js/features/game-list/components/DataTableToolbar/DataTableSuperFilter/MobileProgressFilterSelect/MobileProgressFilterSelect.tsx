import type { Table } from '@tanstack/react-table';
import { useTranslation } from 'react-i18next';
import { LuCircleDot, LuMedal, LuSparkles, LuTrophy } from 'react-icons/lu';

import { BaseLabel } from '@/common/components/+vendor/BaseLabel';
import {
  BaseSelect,
  BaseSelectContent,
  BaseSelectGroup,
  BaseSelectItem,
  BaseSelectLabel,
  BaseSelectTrigger,
  BaseSelectValue,
} from '@/common/components/+vendor/BaseSelect';
import { usePageProps } from '@/common/hooks/usePageProps';

interface MobileProgressFilterSelectProps<TData> {
  table: Table<TData>;
}

export function MobileProgressFilterSelect<TData>({
  table,
}: MobileProgressFilterSelectProps<TData>) {
  const { t } = useTranslation();
  const { auth } = usePageProps();

  const column = table.getColumn('progress');
  const selectedValues = column?.getFilterValue() as string[];
  const playerPreferredMode = auth?.user?.playerPreferredMode ?? 'hardcore';

  const handleValueChange = (value: App.Platform.Enums.GameListProgressFilterValue | 'null') => {
    if (value === 'null') {
      column?.setFilterValue(undefined);

      return;
    }

    column?.setFilterValue([value]);
  };

  return (
    <div className="flex flex-col gap-2">
      <BaseLabel htmlFor="drawer-progress" className="text-neutral-100 light:text-neutral-950">
        {t('Progress')}
      </BaseLabel>

      <BaseSelect value={selectedValues?.[0] ?? 'null'} onValueChange={handleValueChange}>
        <BaseSelectTrigger id="drawer-progress" className="w-full">
          <BaseSelectValue placeholder={t('All Games')} />
        </BaseSelectTrigger>

        <BaseSelectContent>
          <BaseSelectItem value="null" data-testid="all-games-option">
            {t('All Games')}
          </BaseSelectItem>

          <BaseSelectGroup>
            <BaseSelectLabel>{t('Progress')}</BaseSelectLabel>
            <BaseSelectItem Icon={LuSparkles} value="unstarted">
              {t('None')}
            </BaseSelectItem>
            <BaseSelectItem Icon={LuSparkles} value="unfinished">
              {t('Some (No Awards)')}
            </BaseSelectItem>
          </BaseSelectGroup>

          {playerPreferredMode === 'hardcore' ? (
            <>
              <BaseSelectGroup>
                <BaseSelectLabel>{t('Awards - At Least')}</BaseSelectLabel>
                <BaseSelectItem Icon={LuCircleDot} value="gte_beaten_hardcore">
                  {t('Beaten or Higher')}
                </BaseSelectItem>
              </BaseSelectGroup>

              <BaseSelectGroup>
                <BaseSelectLabel>{t('Awards - Exact')}</BaseSelectLabel>
                <BaseSelectItem Icon={LuCircleDot} value="eq_beaten_hardcore">
                  {t('Beaten Only')}
                </BaseSelectItem>
                <BaseSelectItem Icon={LuTrophy} value="eq_mastered">
                  {t('Mastered Only')}
                </BaseSelectItem>
              </BaseSelectGroup>

              <BaseSelectGroup>
                <BaseSelectLabel>{t('Special Filters')}</BaseSelectLabel>
                <BaseSelectItem Icon={LuMedal} value="revised">
                  {t('Missing New Achievements')}
                </BaseSelectItem>
                <BaseSelectItem Icon={LuMedal} value="neq_mastered">
                  {t('Not Yet Mastered')}
                </BaseSelectItem>
              </BaseSelectGroup>
            </>
          ) : playerPreferredMode === 'softcore' ? (
            <>
              <BaseSelectGroup>
                <BaseSelectLabel>{t('Awards - At Least')}</BaseSelectLabel>
                <BaseSelectItem Icon={LuCircleDot} value="gte_beaten_softcore">
                  {t('Beaten (softcore) or Higher')}
                </BaseSelectItem>
              </BaseSelectGroup>

              <BaseSelectGroup>
                <BaseSelectLabel>{t('Awards - Exact')}</BaseSelectLabel>
                <BaseSelectItem Icon={LuCircleDot} value="eq_beaten_softcore">
                  {t('Beaten (softcore) Only')}
                </BaseSelectItem>
                <BaseSelectItem Icon={LuTrophy} value="eq_completed">
                  {t('Completed Only')}
                </BaseSelectItem>
              </BaseSelectGroup>

              <BaseSelectGroup>
                <BaseSelectLabel>{t('Special Filters')}</BaseSelectLabel>
                <BaseSelectItem Icon={LuMedal} value="revised">
                  {t('Missing New Achievements')}
                </BaseSelectItem>
                <BaseSelectItem Icon={LuMedal} value="neq_mastered">
                  {t('Not Yet Completed')}
                </BaseSelectItem>
              </BaseSelectGroup>
            </>
          ) : (
            // Mixed mode.
            <>
              <BaseSelectGroup>
                <BaseSelectLabel>{t('Awards - At Least')}</BaseSelectLabel>
                <BaseSelectItem Icon={LuCircleDot} value="gte_beaten_softcore">
                  {t('Beaten (Softcore) or Higher')}
                </BaseSelectItem>
                <BaseSelectItem Icon={LuCircleDot} value="gte_beaten_hardcore">
                  {t('Beaten (Hardcore) or Higher')}
                </BaseSelectItem>
                <BaseSelectItem Icon={LuTrophy} value="gte_completed">
                  {t('Completed or Higher')}
                </BaseSelectItem>
              </BaseSelectGroup>

              <BaseSelectGroup>
                <BaseSelectLabel>{t('Awards - Exact')}</BaseSelectLabel>
                <BaseSelectItem Icon={LuCircleDot} value="eq_beaten_softcore">
                  {t('Beaten (Softcore) Only')}
                </BaseSelectItem>
                <BaseSelectItem Icon={LuCircleDot} value="eq_beaten_hardcore">
                  {t('Beaten (Hardcore) Only')}
                </BaseSelectItem>
                <BaseSelectItem Icon={LuTrophy} value="eq_completed">
                  {t('Completed Only')}
                </BaseSelectItem>
                <BaseSelectItem Icon={LuTrophy} value="eq_mastered">
                  {t('Mastered Only')}
                </BaseSelectItem>
              </BaseSelectGroup>

              <BaseSelectGroup>
                <BaseSelectLabel>{t('Special Filters')}</BaseSelectLabel>
                <BaseSelectItem Icon={LuMedal} value="revised">
                  {t('Missing New Achievements')}
                </BaseSelectItem>
                <BaseSelectItem Icon={LuMedal} value="neq_mastered">
                  {t('Not Yet Completed/Mastered')}
                </BaseSelectItem>
              </BaseSelectGroup>
            </>
          )}
        </BaseSelectContent>
      </BaseSelect>
    </div>
  );
}
