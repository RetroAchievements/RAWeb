import { GameAvatar } from '@/common/components/GameAvatar';
import type { UserAwardProps } from '@/common/components/UserAward';
import { UserAward, UserPatreonAward } from '@/common/components/UserAward';
import { UserAwardCounter } from '@/common/components/UserAwardCounter/UserAwardCounter';
import { UserAwardList } from '@/common/components/UserAwardList/UserAwardList';
import { usePageProps } from '@/common/hooks/usePageProps';
import type { AwardProps, EventDataProps } from '@/features/reorder-site-awards/components/+root';

export const ReorderSiteAwardsSidebarRoot = () => {
  const { gameAwards, siteAwards, eventAwards, eventData } = usePageProps<{
    gameAwards: AwardProps[];
    siteAwards: AwardProps[];
    eventAwards: AwardProps[];
    eventData: EventDataProps;
  }>();

  const gameAwardAwards = gameAwards
    .sort((award) => award.DisplayOrder)
    .map((award) => (
      <GameAvatar
        key={`game-${award.DisplayOrder}`}
        id={award.AwardData}
        title={award.Title}
        variant={'inline'}
        size={48}
        showLabel={false}
        badgeUrl={award.ImageIcon}
      />
    ));

  const gameAwardList = (
    <UserAwardList
      headingLabel={'Game Awards'}
      headingCountSlot={
        <UserAwardCounter
          icon={'👑'}
          numItems={gameAwardAwards.length}
          text={'A lot of masteries!!'}
        />
      }
      awards={gameAwardAwards}
    />
  );

  const siteAwardAwards = siteAwards
    .sort((award) => award.DisplayOrder)
    .map((award) => {
      const newProps: UserAwardProps = {
        dateAwarded: new Date(award.AwardedAt).toString(),
        imageUrl: award.ImageIcon,
        isGold: true,
        tooltip: 'True',
      };

      return newProps;
    })
    .map((award) => <UserAward award={award} key={`site-${award.dateAwarded}`} size={48} />);

  const siteAwardList = (
    <UserAwardList
      headingLabel={'Site Awards'}
      headingCountSlot={
        <UserAwardCounter icon={'🌐'} text={'Site Awards'} numItems={siteAwardAwards.length} />
      }
      awards={siteAwardAwards}
    />
  );

  return (
    <div data-testid="sidebar" className="flex flex-col gap-6">
      {gameAwardList}
      {siteAwardList}
    </div>
  );
};
