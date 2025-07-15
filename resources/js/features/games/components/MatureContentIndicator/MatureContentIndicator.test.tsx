import { render, screen } from '@/test';

import { MatureContentIndicator } from './MatureContentIndicator';

describe('Component: MatureContentIndicator', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<MatureContentIndicator />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays the mature content text', () => {
    // ARRANGE
    render(<MatureContentIndicator />);

    // ASSERT
    expect(screen.getByText(/mature content/i)).toBeVisible();
  });
});
