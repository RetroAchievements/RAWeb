import { render, screen } from '@/test';

import { RichPresenceMessage } from './RichPresenceMessage';

describe('Component: RichPresenceMessage', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<RichPresenceMessage message="message" gameTitle="gameTitle" />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays the rich presence message', () => {
    // ARRANGE
    render(<RichPresenceMessage message="message" gameTitle="gameTitle" />);

    // ASSERT
    expect(screen.getByText(/message/i)).toBeVisible();
  });

  it('given the rich presence message contains "Unknown macro", falls back to displaying the game title', () => {
    // ARRANGE
    render(<RichPresenceMessage message="Unknown macro" gameTitle="Legend of Zelda" />);

    // ASSERT
    expect(screen.getByText(/playing legend of zelda/i)).toBeVisible();
  });
});
