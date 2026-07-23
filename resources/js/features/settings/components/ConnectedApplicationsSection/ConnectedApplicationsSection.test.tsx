import { render, screen } from '@/test';
import { createConnectedOAuthApplication, createZiggyProps } from '@/test/factories';

import { ConnectedApplicationsSection } from './ConnectedApplicationsSection';

describe('Component: ConnectedApplicationsSection', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<ConnectedApplicationsSection />, {
      pageProps: { connectedOAuthApplications: [], ziggy: createZiggyProps() },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the user has no connections, renders nothing', () => {
    // ARRANGE
    render(<ConnectedApplicationsSection />, {
      pageProps: { connectedOAuthApplications: [], ziggy: createZiggyProps() },
    });

    // ACT
    const heading = screen.queryByRole('heading', {
      name: 'Connected Applications',
    });

    // ASSERT
    expect(heading).not.toBeInTheDocument();
  });

  it('given the user has connections, lists them with the date they were granted', () => {
    // ARRANGE
    render(<ConnectedApplicationsSection />, {
      pageProps: {
        connectedOAuthApplications: [
          createConnectedOAuthApplication({
            name: 'My Connected App',
            connectedAt: '2026-07-12T00:00:00.000Z',
          }),
        ],
        ziggy: createZiggyProps(),
      },
    });

    // ACT
    const heading = screen.getByRole('heading', {
      name: 'Connected Applications',
    });

    // ASSERT
    expect(heading).toBeVisible();
    expect(screen.getByText('My Connected App')).toBeVisible();
    expect(screen.getByText(/connected jul 12, 2026/i)).toBeVisible();
    expect(screen.getByRole('button', { name: 'Revoke' })).toBeVisible();
  });
});
