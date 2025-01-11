import { render, screen } from '@/test';

import { WantToPlayReason } from './WantToPlayReason';

describe('Component: WantToPlayReason', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<WantToPlayReason />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays the correct label', () => {
    // ARRANGE
    render(<WantToPlayReason />);

    // ASSERT
    expect(screen.getByText(/in your backlog/i)).toBeVisible();
  });
});
