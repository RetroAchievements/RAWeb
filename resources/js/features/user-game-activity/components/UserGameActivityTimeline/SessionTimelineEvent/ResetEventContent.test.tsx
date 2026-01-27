import { render, screen } from '@/test';

import { ResetEventContent } from './ResetEventContent';

describe('Component: ResetEventContent', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<ResetEventContent label="Test Label" />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays the provided label', () => {
    // ARRANGE
    render(<ResetEventContent label="Reset Achievement 1: Gotta Go Fast" />);

    // ASSERT
    expect(screen.getByText(/reset achievement 1: gotta go fast/i)).toBeVisible();
  });
});
