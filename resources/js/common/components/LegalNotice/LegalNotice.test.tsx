import { render, screen } from '@/test';

import { LegalNotice } from './LegalNotice';

describe('Component: LegalNotice', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<LegalNotice />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays a necessary legal blurb', () => {
    // ARRANGE
    render(<LegalNotice />);

    // ASSERT
    expect(
      screen.getByText(
        /there are no copyright-protected roms available for download on retroachievements.org/i,
      ),
    ).toBeVisible();
  });
});
