import { render, screen } from '@/test';

import { OAuthBranding } from './OAuthBranding';

describe('Component: OAuthBranding', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<OAuthBranding />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('renders the RetroAchievements logo image', () => {
    // ARRANGE
    render(<OAuthBranding />);

    // ASSERT
    const logo = screen.getByRole('img', { name: /retroachievements/i });
    expect(logo).toBeVisible();
    expect(logo).toHaveAttribute('src', '/assets/images/ra-icon.webp');
  });

  it('given no initial prop, still renders correctly', () => {
    // ARRANGE
    render(<OAuthBranding />);

    // ASSERT
    const logo = screen.getByRole('img', { name: /retroachievements/i });
    expect(logo).toBeVisible();
  });

  it('given an initial prop as false, renders correctly', () => {
    // ARRANGE
    render(<OAuthBranding initial={false} />);

    // ASSERT
    const logo = screen.getByRole('img', { name: /retroachievements/i });
    expect(logo).toBeVisible();
  });

  it('given an initial prop as an object, renders correctly', () => {
    // ARRANGE
    const initialAnimation = { opacity: 0, y: -20 };
    render(<OAuthBranding initial={initialAnimation} />);

    // ASSERT
    const logo = screen.getByRole('img', { name: /retroachievements/i });
    expect(logo).toBeInTheDocument();
  });
});
