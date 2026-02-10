import type { FC } from 'react';

export type AwardProps = {
  imageUrl: string;
  tooltip: string;
  link?: string;
  isGold?: boolean;
  gameId?: number;
  dateAwarded: string;
};

export const UserAward: FC<{ award: AwardProps; size?: number }> = ({ award, size = 64 }) => {
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

  return (
    <div data-gameid={award.gameId} data-date={award.dateAwarded}>
      {award.link ? <a href={award.link}>{img}</a> : img}
    </div>
  );
};
