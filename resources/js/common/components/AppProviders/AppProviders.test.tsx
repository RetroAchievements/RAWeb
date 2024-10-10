import { render, screen } from '@/test';

import { AppProviders } from './AppProviders';

describe('Component: AppProviders', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<AppProviders>content</AppProviders>, {
      wrapper: (props) => <div {...props}></div>,
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('renders children', () => {
    // ARRANGE
    render(<AppProviders>content</AppProviders>, { wrapper: (props) => <div {...props} /> });

    // ASSERT
    expect(screen.getByText(/content/i)).toBeVisible();
  });

  it('given react query devtools are enabled, loads the tools', () => {
    // ARRANGE
    vi.stubEnv('VITE_REACT_QUERY_DEVTOOLS_ENABLED', 'true');

    render(<AppProviders>content</AppProviders>, { wrapper: (props) => <div {...props} /> });

    // ASSERT
    expect(screen.getByTestId('query-devtools')).toBeVisible();
  });

  it('given react query devtools are not enabled, does not load the tools', () => {
    // ARRANGE
    vi.stubEnv('VITE_REACT_QUERY_DEVTOOLS_ENABLED', 'false');

    render(<AppProviders>content</AppProviders>, { wrapper: (props) => <div {...props} /> });

    // ASSERT
    expect(screen.queryByTestId('query-devtools')).not.toBeInTheDocument();
  });
});
