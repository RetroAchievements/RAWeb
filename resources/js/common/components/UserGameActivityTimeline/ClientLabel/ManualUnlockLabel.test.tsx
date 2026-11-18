import { render, screen } from '@/test';
import { createUser } from '@/test/factories';

import { ManualUnlockLabel } from './ManualUnlockLabel';

describe('Component: ManualUnlockLabel', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const unlocker = createUser({
      displayName: 'Searo',
    });

    const { container } = render(<ManualUnlockLabel unlocker={unlocker} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays the label correctly', () => {
    // ARRANGE
    const unlocker = createUser({
      displayName: 'Searo',
    });

    render(<ManualUnlockLabel unlocker={unlocker} />);

    // ASSERT
    expect(screen.getByRole('img', { name: /searo/i })).toBeVisible();

    expect(screen.getByText(/searo/i)).toBeVisible();
    expect(screen.getByText(/awarded a manual unlock/i)).toBeVisible();
  });
});
