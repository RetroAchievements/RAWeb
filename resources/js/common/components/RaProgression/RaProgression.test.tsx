import { render, screen } from '@/test';

import { RaProgression } from './RaProgression';

describe('Component: RaProgression', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<RaProgression />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given custom props are provided, applies them to the svg element', () => {
    // ARRANGE
    const testId = 'test-icon';
    const className = 'custom-class';

    // ACT
    render(<RaProgression data-testid={testId} className={className} />);

    // ASSERT
    const svgElement = screen.getByTestId(testId);
    expect(svgElement).toBeVisible();
    expect(svgElement).toHaveClass(className);
  });
});
