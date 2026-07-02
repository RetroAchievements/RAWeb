import type { FC } from 'react';
import UserAwardData = App.Community.Data.UserAwardData;

export const UserAward: FC<{ award: UserAwardData; size?: number }> = ({ award, size = 64 }) => {
  const img = (
    <img
      src={award.imageUrl}
      title={award.tooltip}
      alt={award.tooltip}
      width={size}
      height={size}
      className={award.isGold ? 'goldimage' : 'badgeimg siteawards'}
    />
  );

  /*
  <div class='p-2 max-w-[320px] text-pretty'><span>$tooltip</span><p class='italic'>{$awardDate}</p></div>
   */

  return (
    <div
      data-gameid={award.gameId}
      data-date={award.dateAwarded}
      className="max-w-[320px] text-pretty p-2"
    >
      <span>{award.link ? <a href={award.link}>{img}</a> : img}</span>
    </div>
  );
};
