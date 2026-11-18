import { render, screen } from '@/test';

import { TicketLogicVersionLink } from './TicketLogicVersionLink';

describe('Component: TicketLogicVersionLink', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render<App.Platform.Data.TicketShowPageProps>(
      <TicketLogicVersionLink achievementId={88} version={5} />,
      { pageProps: { can: {} } },
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the user has logic viewing permission, links the version tag to the logic editor', () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(
      <TicketLogicVersionLink achievementId={88} version={5} />,
      { pageProps: { can: { viewAchievementLogic: true } } },
    );

    // ASSERT
    expect(screen.getByRole('link', { name: 'v5' })).toHaveAttribute(
      'href',
      '/manage/achievements/88/logic?version=5',
    );
  });

  it('given the user lacks logic viewing permission, displays the version tag as plain text', () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(
      <TicketLogicVersionLink achievementId={88} version={5} />,
      { pageProps: { can: {} } },
    );

    // ASSERT
    expect(screen.getByText('v5')).toBeVisible();
    expect(screen.queryByRole('link')).not.toBeInTheDocument();
  });
});
