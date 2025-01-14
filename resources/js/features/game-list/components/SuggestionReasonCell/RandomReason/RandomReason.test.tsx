import { render, screen } from '@/test';

import { RandomReason } from './RandomReason';

describe('Component: RandomReason', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<RandomReason />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays the correct label', () => {
    // ARRANGE
    render(<RandomReason />);

    // ASSERT
    expect(screen.getByText(/randomly selected/i)).toBeVisible();
  });
});
