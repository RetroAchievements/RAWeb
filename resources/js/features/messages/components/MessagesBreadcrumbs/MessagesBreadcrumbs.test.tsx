import i18n from '@/i18n-client';
import { render, screen } from '@/test';
import { createUser } from '@/test/factories';
import type { TranslatedString } from '@/types/i18next';

import { MessagesBreadcrumbs } from './MessagesBreadcrumbs';

describe('Component: MessagesBreadcrumbs', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <MessagesBreadcrumbs t_currentPageLabel={i18n.t('Inbox')} user={createUser()} />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given a user, has a link to the user profile', () => {
    // ARRANGE
    render(
      <MessagesBreadcrumbs
        t_currentPageLabel={i18n.t('Inbox')}
        user={createUser({ displayName: 'Scott' })}
      />,
    );

    // ASSERT
    const userProfileLinkEl = screen.getByRole('link', { name: /scott/i });
    expect(userProfileLinkEl).toBeVisible();
    expect(userProfileLinkEl).toHaveAttribute('href', 'user.show,Scott');
  });

  it('given no user is provided, does not show a user profile link', () => {
    // ARRANGE
    render(<MessagesBreadcrumbs t_currentPageLabel={i18n.t('Inbox')} />);

    // ASSERT
    expect(screen.queryByRole('link', { name: /scott/i })).not.toBeInTheDocument();
  });

  it('shows the current page label', () => {
    // ARRANGE
    const currentPage = 'Test Page';
    render(<MessagesBreadcrumbs t_currentPageLabel={currentPage as TranslatedString} />);

    // ASSERT
    expect(screen.getByText(currentPage)).toBeVisible();
  });
});
