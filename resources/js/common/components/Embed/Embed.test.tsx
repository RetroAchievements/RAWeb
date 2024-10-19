import { render, screen } from '@/test';

import { Embed } from './Embed';

describe('Component: Embed', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<Embed>stuff</Embed>);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('renders children', () => {
    // ARRANGE
    render(<Embed>stuff</Embed>);

    // ASSERT
    expect(screen.getByText(/stuff/i)).toBeVisible();
  });
});
