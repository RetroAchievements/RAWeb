import { render, screen } from '@/test';

import { RaMissable } from './RaMissable';

describe('Component: RaMissable', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<RaMissable />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given custom props are provided, applies them to the svg element', () => {
    // ARRANGE
    const testId = 'test-icon';
    const className = 'custom-class';

    // ACT
    render(<RaMissable data-testid={testId} className={className} />);

    // ASSERT
    const svgElement = screen.getByTestId(testId);
    expect(svgElement).toBeVisible();
    expect(svgElement).toHaveClass(className);
  });

  it('given the component is rendered, has the correct viewBox attribute', () => {
    // ARRANGE
    render(<RaMissable data-testid="test-icon" />);

    // ASSERT
    const svgElement = screen.getByTestId('test-icon');
    expect(svgElement).toHaveAttribute('viewBox', '0 0 24 24');
  });

  it('given the component is rendered, uses currentColor for fill', () => {
    // ARRANGE
    render(<RaMissable data-testid="test-icon" />);

    // ASSERT
    const svgElement = screen.getByTestId('test-icon');
    expect(svgElement).toHaveAttribute('fill', 'currentColor');
  });
});
