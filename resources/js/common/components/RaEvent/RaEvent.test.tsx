import { render, screen } from '@/test';

import { RaEvent } from './RaEvent';

describe('Component: RaEvent', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<RaEvent />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given custom props are provided, applies them to the svg element', () => {
    // ARRANGE
    const testId = 'test-icon';
    const className = 'custom-class';

    // ACT
    render(<RaEvent data-testid={testId} className={className} />);

    // ASSERT
    const svgElement = screen.getByTestId(testId);
    expect(svgElement).toBeVisible();
    expect(svgElement).toHaveClass(className);
  });

  it('given the component is rendered, has the correct viewBox attribute', () => {
    // ARRANGE
    render(<RaEvent data-testid="test-icon" />);

    // ASSERT
    const svgElement = screen.getByTestId('test-icon');
    expect(svgElement).toHaveAttribute('viewBox', '0 0 36 36');
  });

  it('given the component is rendered, uses currentColor for fill', () => {
    // ARRANGE
    render(<RaEvent data-testid="test-icon" />);

    // ASSERT
    const svgElement = screen.getByTestId('test-icon');
    expect(svgElement).toHaveAttribute('fill', 'currentColor');
  });
});
