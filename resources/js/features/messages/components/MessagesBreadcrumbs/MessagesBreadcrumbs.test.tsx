import i18n from '@/i18n-client';
import { render, screen } from '@/test';
import { createUser } from '@/test/factories';

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

  it('has a link to the user profile', () => {
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
});
