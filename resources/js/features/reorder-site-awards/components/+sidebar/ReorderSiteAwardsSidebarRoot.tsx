import { GameAvatar } from '@/common/components/GameAvatar';
import { UserAward } from '@/common/components/UserAward';
import { UserAwardCounter } from '@/common/components/UserAwardCounter/UserAwardCounter';
import { UserAwardList } from '@/common/components/UserAwardList/UserAwardList';
import { usePageProps } from '@/common/hooks/usePageProps';
import UserAwardData = App.Community.Data.UserAwardData;

export const ReorderSiteAwardsSidebarRoot = () => {
  const { awards } = usePageProps<{
    awards: UserAwardData[];
  }>();

  console.log('awards', awards);

  const gameAwardAwards = awards
    .sort((award) => award.displayOrder)
    .filter((award) => award.awardSection === 'game')
    .map((award) => (
      <GameAvatar
        key={`game-${award.gameId}`}
        id={award.gameId || 0}
        title={award.title}
        variant={'inline'}
        size={48}
        showLabel={false}
        badgeUrl={award.imageUrl}
        // TODO: Handle Mastery css
      />
    ));

  const masteries = awards.filter((award) => award.isGold).length;
  const gameAwardList = (
    <UserAwardList
      headingLabel={'Game Awards'}
      headingCountSlot={
        <>
          <UserAwardCounter icon={'👑'} numItems={masteries} text={`games mastered`} />
          <UserAwardCounter
            icon={'🎖'}
            numItems={gameAwardAwards.length - masteries}
            text={`games completed`}
          />
        </>
      }
      awards={gameAwardAwards}
    />
  );

  const siteAwardAwards = awards
    .sort((award) => award.displayOrder)
    .filter((award) => award.awardSection === 'site')
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

  const eventAwardAwards = awards
    .sort((award) => award.displayOrder)
    .filter((award) => award.awardSection === 'event')
    .map((award) => <UserAward award={award} key={`event-${award.dateAwarded}`} size={48} />);

  const eventAwardList = (
    <UserAwardList
      headingLabel={'Event Awards'}
      headingCountSlot={
        <UserAwardCounter icon={'🌱'} text={'Event Awards'} numItems={eventAwardAwards.length} />
      }
      awards={eventAwardAwards}
    />
  );

  return (
    <div data-testid="sidebar" className="flex flex-col gap-6">
      {gameAwardList}
      {siteAwardList}
      {eventAwardList}
    </div>
  );
};
