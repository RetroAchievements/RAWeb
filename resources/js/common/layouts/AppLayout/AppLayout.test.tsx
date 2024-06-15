import { render, screen } from '@/test';

import { AppLayout } from './AppLayout';

describe('Component: AppLayout', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<AppLayout withSidebar={false}>Hello, world</AppLayout>);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('renders children', () => {
    // ARRANGE
    render(<AppLayout withSidebar={false}>Hello, world</AppLayout>);

    // ASSERT
    expect(screen.getByText(/hello, world/i)).toBeVisible();
  });
});
