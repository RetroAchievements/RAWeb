import { render, screen } from '@/test';

import { GuestWelcomeCta } from './GuestWelcomeCta';

describe('Component: GuestWelcomeCta', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<GuestWelcomeCta />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays an accesible heading', () => {
    // ARRANGE
    render(<GuestWelcomeCta />);

    // ASSERT
    expect(screen.getByRole('heading', { name: /welcome/i })).toBeVisible();
  });

  it('displays some introductory welcome text', () => {
    // ARRANGE
    render(<GuestWelcomeCta />);

    // ASSERT
    expect(screen.getByText(/build your profile/i)).toBeVisible();
    expect(screen.getByText(/track your progress/i)).toBeVisible();
    expect(screen.getByText(/classic games/i)).toBeVisible();
  });

  it('has an accessible link to the downloads page', () => {
    // ARRANGE
    render(<GuestWelcomeCta />);

    // ASSERT
    const linkEl = screen.getByRole('link', { name: /the emulators/i });

    expect(linkEl).toBeVisible();
    expect(linkEl).toHaveAttribute('href', 'download.index');
  });

  it('has an accessible link to the all games page', () => {
    // ARRANGE
    render(<GuestWelcomeCta />);

    // ASSERT
    const linkEl = screen.getByRole('link', { name: /the games/i });

    expect(linkEl).toBeVisible();
    expect(linkEl).toHaveAttribute('href', expect.stringContaining('game.index'));
  });

  it('has an accessible link to some random game', () => {
    // ARRANGE
    render(<GuestWelcomeCta />);

    // ASSERT
    const linkEl = screen.getByRole('link', {
      name: /which of these achievements do you think you can get/i,
    });

    expect(linkEl).toBeVisible();
    expect(linkEl).toHaveAttribute('href');

    const hrefValue = linkEl.getAttribute('href');
    expect(hrefValue).toMatch(/game.show/i);
  });
});
