import { render, screen } from '@/test';

import { NewsCategoryLabel } from './NewsCategoryLabel';

describe('Component: NewsCategoryLabel', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<NewsCategoryLabel category="events" />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays the label', () => {
    // ARRANGE
    render(<NewsCategoryLabel category="events" />);

    // ASSERT
    expect(screen.getByText(/events/i)).toBeVisible();
  });
});
