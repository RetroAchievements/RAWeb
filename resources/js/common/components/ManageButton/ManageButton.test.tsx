import { render, screen } from '@/test';

import { ManageButton } from './ManageButton';

describe('Component: ManageButton', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<ManageButton href="https://example.com" />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given an href, renders a link with that target URL', () => {
    // ARRANGE
    const testUrl = 'https://test.com';
    render(<ManageButton href={testUrl} />);

    // ASSERT
    const link = screen.getByRole('link');
    expect(link).toHaveAttribute('href', testUrl);
  });

  it('given the link is rendered, opens in a new tab', () => {
    // ARRANGE
    render(<ManageButton href="https://example.com" />);

    // ASSERT
    const link = screen.getByRole('link');
    expect(link).toHaveAttribute('target', '_blank');
  });

  it('given the component is rendered, shows the manage text', () => {
    // ARRANGE
    render(<ManageButton href="https://example.com" />);

    // ASSERT
    expect(screen.getByText(/manage/i)).toBeVisible();
  });

  it('given a className prop, applies it to the link element', () => {
    // ARRANGE
    const testClass = 'test-class';
    render(<ManageButton href="https://example.com" className={testClass} />);

    // ASSERT
    const link = screen.getByRole('link');
    expect(link.className).toContain(testClass);
  });
});
