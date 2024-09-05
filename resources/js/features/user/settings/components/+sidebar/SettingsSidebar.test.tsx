import { render, screen } from '@/test';

import { SettingsSidebar } from './SettingsSidebar';

describe('Component: SettingsSidebar', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<SettingsSidebar />, {
      pageProps: { can: { updateAvatar: true }, auth: { user: {} } },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the user is not muted, renders the avatar section', () => {
    // ARRANGE
    render(<SettingsSidebar />, {
      pageProps: { can: { updateAvatar: true }, auth: { user: { isMuted: false } } },
    });

    // ASSERT
    expect(screen.getByRole('heading', { name: /avatar/i })).toBeVisible();
  });

  it('given the user is muted, does not render the avatar section', () => {
    // ARRANGE
    render(<SettingsSidebar />, {
      pageProps: { can: { updateAvatar: true }, auth: { user: { isMuted: true } } },
    });

    // ASSERT
    expect(screen.queryByRole('heading', { name: /avatar/i })).not.toBeInTheDocument();
  });
});
