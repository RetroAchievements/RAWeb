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

  it('renders provided content into the main and sidebar components', () => {
    // ARRANGE
    render(
      <AppLayout withSidebar={true}>
        <AppLayout.Main>main content!</AppLayout.Main>
        <AppLayout.Sidebar>sidebar content!</AppLayout.Sidebar>
      </AppLayout>,
    );

    // ASSERT
    expect(screen.getByText(/main content/i)).toBeVisible();
    expect(screen.getByText(/sidebar content/i)).toBeVisible();
  });
});
