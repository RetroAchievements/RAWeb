import { render, screen } from '@/test';

import { SubsetButtonChip } from './SubsetButtonChip';

describe('Component: SubsetButtonChip', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<SubsetButtonChip />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays the translated subset text', () => {
    // ARRANGE
    render(<SubsetButtonChip />);

    // ASSERT
    expect(screen.getByText(/subset/i)).toBeVisible();
  });
});
