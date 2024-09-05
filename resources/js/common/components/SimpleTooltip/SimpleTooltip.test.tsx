import { render, screen } from '@/test';

import { SimpleTooltip } from './SimpleTooltip';

describe('Component: SimpleTooltip', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <SimpleTooltip tooltipContent="Hello, world!">content</SimpleTooltip>,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('renders children', () => {
    // ARRANGE
    render(<SimpleTooltip tooltipContent="Hello, world!">content</SimpleTooltip>);

    // ASSERT
    expect(screen.getByText(/content/i)).toBeVisible();
  });
});
