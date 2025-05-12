import { route } from 'ziggy-js';

import { render, screen } from '@/test';

import { SeeMoreLink } from './SeeMoreLink';

describe('Component: SeeMoreLink', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<SeeMoreLink href="#" />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays an accessible link', () => {
    // ARRANGE
    render(<SeeMoreLink href="https://google.com" />);

    // ASSERT
    const linkEl = screen.getByRole('link');

    expect(linkEl).toBeVisible();
    expect(linkEl).toHaveAttribute('href', 'https://google.com');
  });

  it('still displays an accessible link if client-side routing is enabled', () => {
    // ARRANGE
    render(<SeeMoreLink href={route('contact')} asClientSideRoute={true} />);

    // ASSERT
    const linkEl = screen.getByRole('link');

    expect(linkEl).toBeVisible();
    expect(linkEl).toHaveAttribute('href', 'contact');
  });
});
