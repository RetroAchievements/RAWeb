import { render, screen } from '@/test';

import { MutedMessage } from './MutedMessage';

describe('Component: MutedMessage', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<MutedMessage mutedUntil="2024-02-10T15:30:00Z" />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given a muted until date, displays the formatted date in the message', () => {
    // ARRANGE
    render(<MutedMessage mutedUntil="2024-02-10T15:30:00Z" />);

    // ASSERT
    expect(screen.getByText(/you are muted until feb 10, 2024\./i)).toBeVisible();
  });
});
