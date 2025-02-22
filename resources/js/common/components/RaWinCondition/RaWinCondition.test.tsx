import { render, screen } from '@/test';

import { RaWinCondition } from './RaWinCondition';

describe('Component: RaWinCondition', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<RaWinCondition />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given custom props are provided, applies them to the svg element', () => {
    // ARRANGE
    const testId = 'test-icon';
    const className = 'custom-class';

    // ACT
    render(<RaWinCondition data-testid={testId} className={className} />);

    // ASSERT
    const svgElement = screen.getByTestId(testId);
    expect(svgElement).toBeVisible();
    expect(svgElement).toHaveClass(className);
  });

  it('given the component is rendered, has the correct svg styling attributes', () => {
    // ARRANGE
    render(<RaWinCondition data-testid="test-icon" />);

    // ASSERT
    const svgElement = screen.getByTestId('test-icon');
    expect(svgElement).toHaveAttribute('viewBox', '0 0 20 20');
    expect(svgElement).toHaveAttribute('stroke', 'currentColor');
    expect(svgElement).toHaveAttribute('fill', 'none');
  });
});
