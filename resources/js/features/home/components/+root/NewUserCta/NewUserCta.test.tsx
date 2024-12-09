import { render, screen } from '@/test';

import { NewUserCta } from './NewUserCta';

describe('Component: NewUserCta', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<NewUserCta />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays an accessible heading', () => {
    // ARRANGE
    render(<NewUserCta />);

    // ASSERT
    expect(screen.getByRole('heading', { name: /getting started/i })).toBeVisible();
  });

  it('has an accessible link to the FAQ', () => {
    // ARRANGE
    render(<NewUserCta />);

    // ASSERT
    const linkEl = screen.getByRole('link', { name: /comprehensive faq/i });

    expect(linkEl).toBeVisible();
    expect(linkEl).toHaveAttribute('href', 'https://docs.retroachievements.org/general/faq.html');
  });
});
