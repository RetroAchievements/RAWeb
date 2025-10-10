import { render, screen, waitFor } from '@/test';

import { BootState } from './BootState';

describe('Component: BootState', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<BootState />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays some welcome text', async () => {
    // ARRANGE
    render(<BootState />);

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/type at least 3 characters/i)).toBeVisible();
    });
  });
});
