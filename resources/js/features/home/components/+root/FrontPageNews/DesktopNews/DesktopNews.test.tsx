import userEvent from '@testing-library/user-event';

import { render, screen, waitFor } from '@/test';
import { createNews, createZiggyProps } from '@/test/factories';

import { DesktopNews } from './DesktopNews';

describe('Component: DesktopNews', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render<App.Http.Data.HomePageProps>(<DesktopNews />, {
      pageProps: { recentNews: [] },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there is no news, renders nothing', () => {
    // ARRANGE
    render<App.Http.Data.HomePageProps>(<DesktopNews />, {
      pageProps: { recentNews: [], ziggy: createZiggyProps({ device: 'desktop' }) },
    });

    // ASSERT
    expect(screen.queryByTestId('desktop-news')).not.toBeInTheDocument();
  });

  it('given the user paginates to the next page, shows the next page', async () => {
    // ARRANGE
    render<App.Http.Data.HomePageProps>(<DesktopNews />, {
      pageProps: {
        recentNews: [
          createNews({ title: 'Title 1' }),
          createNews({ title: 'Title 2' }),
          createNews({ title: 'Title 3' }),
          createNews({ title: 'Title 4' }),
          createNews({ title: 'Title 5' }),
          createNews({ title: 'Title 6' }),
        ],
        ziggy: createZiggyProps({ device: 'desktop' }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /next news page/i }));

    // ASSERT
    // ... have to wait for AnimatePresence ...
    await waitFor(() => {
      expect(screen.getByText(/title 4/i)).toBeVisible();
    });
    await waitFor(() => {
      expect(screen.getByText(/title 5/i)).toBeVisible();
    });
    await waitFor(() => {
      expect(screen.getByText(/title 6/i)).toBeVisible();
    });

    expect(screen.queryByText(/title 1/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/title 2/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/title 3/i)).not.toBeInTheDocument();
  });

  it('given the user paginates to the previous page, shows the previous page', async () => {
    // ARRANGE
    render<App.Http.Data.HomePageProps>(<DesktopNews />, {
      pageProps: {
        recentNews: [
          createNews({ title: 'Title 1' }),
          createNews({ title: 'Title 2' }),
          createNews({ title: 'Title 3' }),
          createNews({ title: 'Title 4' }),
          createNews({ title: 'Title 5' }),
          createNews({ title: 'Title 6' }),
        ],
        ziggy: createZiggyProps({ device: 'desktop' }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /next news page/i }));

    // wait...
    await waitFor(() => {
      expect(screen.getByText(/title 6/i)).toBeVisible();
    });

    // now go back...
    await userEvent.click(screen.getByRole('button', { name: /previous news page/i }));

    // ASSERT
    // ... have to wait for AnimatePresence ...
    await waitFor(() => {
      expect(screen.getByText(/title 1/i)).toBeVisible();
    });
    await waitFor(() => {
      expect(screen.getByText(/title 2/i)).toBeVisible();
    });
    await waitFor(() => {
      expect(screen.getByText(/title 3/i)).toBeVisible();
    });

    expect(screen.queryByText(/title 4/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/title 5/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/title 6/i)).not.toBeInTheDocument();
  });
});
