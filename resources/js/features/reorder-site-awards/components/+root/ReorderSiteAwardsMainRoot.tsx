import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { usePageProps } from '@/common/hooks/usePageProps';
import { AwardOrderTable } from '@/features/reorder-site-awards/components/AwardOrderTable';

import { ResetOrderButton } from '../ResetOrderButton';

export interface AwardProps {
  AwardData: number;
  AwardDataExtra: number;
  AwardType: number;
  AwardedAt: number;
  ConsoleID: number;
  ConsoleName: string;
  DisplayOrder: number;
  ImageIcon: string;
  Title: string;
}

export interface EventDataProps {
  active_from: string;
  active_through: string;
  active_until: string;
  created_at: string;
  gives_site_award: boolean;
  id: number;
  image_asset_path: string;
  legacy_game: {
    achievement_set_version_hash: string;
    achievements_published: number;
    id: number;
    image_box_art_asset_path: string;
    image_icon_asset_path: string;
    image_ingame_asset_path: string;
    image_title_asset_path: string;
    players_total: number;
    points_total: number;
    publisher: string;
    sort_title: string;
    system_id: number;
    title: string;
    updated_at: string;
  };
  legacy_game_id: number;
  updated_at: string;
}

export const ReorderSiteAwardsMainRoot: FC = () => {
  const { gameAwards, siteAwards, eventAwards, eventData } = usePageProps<{
    gameAwards: AwardProps[];
    siteAwards: AwardProps[];
    eventAwards: AwardProps[];
    eventData: EventDataProps;
  }>();
  console.log(gameAwards, siteAwards, eventAwards, eventData);
  const { t } = useTranslation();

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
        <button /* onClick="handleSaveAllClick()" */ className="btn">Save All Changes</button>
      </div>

      {/*<AwardOrderTable*/}
      {/*  title={'Game Awards'}*/}
      {/*  awards={gameAwards}*/}
      {/*  username={'user'}*/}
      {/*  awardCounter={0}*/}
      {/*  renderedSectionCount={9}*/}
      {/*  prefersSeeingSavedHiddenRows={true}*/}
      {/*  initialSectionOrder={0}*/}
      {/*  eventData={eventData}*/}
      {/*/>*/}
    </div>
  );
};
