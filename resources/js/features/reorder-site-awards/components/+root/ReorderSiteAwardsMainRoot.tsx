import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { usePageProps } from '@/common/hooks/usePageProps';
import { AwardOrderTableOld } from '@/features/reorder-site-awards/components/AwardOrderTableOld';

import { ResetOrderButton } from '../ResetOrderButton';
import UserAwardData = App.Community.Data.UserAwardData;

export const ReorderSiteAwardsMainRoot: FC = () => {
  const { awards } = usePageProps<{
    awards: UserAwardData[];
  }>();
  const { t } = useTranslation();

  const saveAllChangesButton = () => {
    const mappedTableRows = reorderSiteAwards.collectMappedTableRows();

    try {
      const withComputedDisplayOrderValues =
        reorderSiteAwards.computeDisplayOrderValues(mappedTableRows);

      postAllAwardsDisplayOrder(withComputedDisplayOrderValues);
      reorderSiteAwards.moveHiddenRowsToTop();
    } catch (error) {
      showStatusFailure(error.toString());
    }
  };

  return (
    <div className="flex flex-col gap-3">
      <h2 id="reorder-site-awards-header">{t('Reorder Site Awards')}</h2>

      <div className="embedded grid gap-y-4">
        <p>
          To rearrange your site awards, drag and drop the award rows or use the buttons within each
          row to move them up or down. Award categories can be reordered using the dropdown menus
          next to each category name. Remember to save your changes before leaving by clicking the
          "Save All Changes" button.
        </p>
      </div>

      <div className="mb-6 mt-3 flex w-full items-center justify-between">
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center">
          <label className="flex items-center gap-x-1">
            <input
              type="checkbox"
              // onChange={handleShowSavedHiddenRowsChange(event)}
              // $showSavedHiddenRowsCheckedAttribute
            />
            Show previously hidden badges
          </label>
          <ResetOrderButton />
        </div>
        <button onClick={saveAllChangesButton} className="btn">
          {t('Save All Changes')}
        </button>
      </div>

      <AwardOrderTableOld
        title={'Game Awards'}
        awards={awards}
        awardCounter={0}
        renderedSectionCount={9}
        prefersSeeingSavedHiddenRows={true}
        initialSectionOrder={0}
      />
    </div>
  );
};
