import { render, screen } from '@/test';

import { RichPresenceEventContent } from './RichPresenceEventContent';

describe('Component: RichPresenceEventContent', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<RichPresenceEventContent label="Test Label" />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays the provided label', () => {
    // ARRANGE
    render(<RichPresenceEventContent label="Playing Level 1" />);

    // ASSERT
    expect(screen.getByText(/playing level 1/i)).toBeVisible();
  });
});
