import { render, screen } from '@/test';

import { RevisedReason } from './RevisedReason';

describe('Component: RevisedReason', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<RevisedReason />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays the correct label', () => {
    // ARRANGE
    render(<RevisedReason />);

    // ASSERT
    expect(screen.getByText(/revised/i)).toBeVisible();
  });
});
