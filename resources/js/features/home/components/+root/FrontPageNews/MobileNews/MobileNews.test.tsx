import { render, screen } from '@/test';
import { createNews, createZiggyProps } from '@/test/factories';

import { MobileNews } from './MobileNews';

describe('Component: MobileNews', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render<App.Http.Data.HomePageProps>(<MobileNews />, {
      pageProps: { recentNews: [] },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there is no news, renders nothing', () => {
    // ARRANGE
    render<App.Http.Data.HomePageProps>(<MobileNews />, {
      pageProps: { recentNews: [], ziggy: createZiggyProps({ device: 'mobile' }) },
    });

    // ASSERT
    expect(screen.queryByTestId('mobile-news')).not.toBeInTheDocument();
  });

  it('still allows news posts that dont have associated links', () => {
    // ARRANGE
    const news = createNews({ link: null });

    render<App.Http.Data.HomePageProps>(<MobileNews />, {
      pageProps: {
        recentNews: [news],
        ziggy: createZiggyProps({ device: 'mobile' }),
      },
    });

    // ASSERT
    expect(screen.getByText(news.title)).toBeVisible();
  });

  it('still allows news posts that dont have associated images', () => {
    // ARRANGE
    const news = createNews({ imageAssetPath: null });

    render<App.Http.Data.HomePageProps>(<MobileNews />, {
      pageProps: {
        recentNews: [news],
        ziggy: createZiggyProps({ device: 'mobile' }),
      },
    });

    // ASSERT
    expect(screen.getByText(news.title)).toBeVisible();
    expect(screen.getByTestId('fallback-image')).toBeVisible();
  });
});
