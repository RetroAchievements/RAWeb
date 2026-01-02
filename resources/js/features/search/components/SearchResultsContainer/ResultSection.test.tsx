import { render, screen } from '@/test';

import { ResultSection } from './ResultSection';

describe('Component: ResultSection', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <ResultSection title="Users" icon={<span data-testid="icon">icon</span>}>
        children
      </ResultSection>,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('renders the title and icon', () => {
    // ARRANGE
    render(
      <ResultSection title="Games" icon={<span data-testid="test-icon">icon</span>}>
        children
      </ResultSection>,
    );

    // ASSERT
    expect(screen.getByText(/games/i)).toBeVisible();
    expect(screen.getByTestId('test-icon')).toBeVisible();
  });

  it('renders children', () => {
    // ARRANGE
    render(
      <ResultSection title="Users" icon={<span>icon</span>}>
        <div>Result Item 1</div>
        <div>Result Item 2</div>
      </ResultSection>,
    );

    // ASSERT
    expect(screen.getByText(/result item 1/i)).toBeVisible();
    expect(screen.getByText(/result item 2/i)).toBeVisible();
  });
});
