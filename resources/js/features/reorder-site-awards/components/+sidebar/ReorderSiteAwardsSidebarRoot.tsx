import { UserAward, UserPatreonAward } from '@/common/components/UserAward';
import { UserAwardList } from '@/common/components/UserAwardList/UserAwardList';

export const ReorderSiteAwardsSidebarRoot = () => {
  return (
    <UserAwardList
      headingLabel={'User Awards'}
      headingCountSlot={undefined}
      awards={[
        <UserPatreonAward key={'patreon'} dateAwarded={'2026-01-01'} size={48} />,
        <UserAward
          key={'test'}
          award={{
            imageUrl: '/',
            tooltip: 'true',
            dateAwarded: '2026-01-01',
            gameId: 1,
          }}
        />,
      ]}
    ></UserAwardList>
  );
};
