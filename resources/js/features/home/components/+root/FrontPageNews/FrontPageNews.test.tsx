import { faker } from '@faker-js/faker';
import dayjs from 'dayjs';

import { fireEvent, render, screen } from '@/test';
import { createHomePageProps, createNews, createZiggyProps } from '@/test/factories';

import { FrontPageNews } from './FrontPageNews';

describe('Component: FrontPageNews', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render<App.Http.Data.HomePageProps>(<FrontPageNews />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays an accessible heading', () => {
    // ARRANGE
    render<App.Http.Data.HomePageProps>(<FrontPageNews />, {
      pageProps: { ...createHomePageProps(), ziggy: createZiggyProps({ device: 'desktop' }) },
    });

    // ASSERT
    expect(screen.getByRole('heading', { name: /news/i })).toBeVisible();
  });

  it('displays news article headings', () => {
    // ARRANGE
    const recentNews = [
      createNews({ title: 'Foo' }),
      createNews({ title: 'Bar' }),
      createNews({ title: 'Baz' }),
    ];

    render<App.Http.Data.HomePageProps>(<FrontPageNews />, {
      pageProps: {
        recentNews,
        ziggy: createZiggyProps({ device: 'desktop' }),
      },
    });

    // ASSERT
    expect(screen.getByText('Foo')).toBeVisible();
    expect(screen.getByText('Bar')).toBeVisible();
    expect(screen.getByText('Baz')).toBeVisible();
  });

  it('displays news article lead text', () => {
    // ARRANGE
    const recentNews = [createNews(), createNews(), createNews()];

    render<App.Http.Data.HomePageProps>(<FrontPageNews />, {
      pageProps: {
        recentNews,
        ziggy: createZiggyProps({ device: 'desktop' }),
      },
    });

    // ASSERT
    expect(screen.getByText(recentNews[0].body)).toBeVisible();
    expect(screen.getByText(recentNews[1].body)).toBeVisible();
    expect(screen.getByText(recentNews[2].body)).toBeVisible();
  });

  it('given the news image fails to load, shows the fallback', () => {
    // ARRANGE
    const newsWithBrokenImage = createNews({
      imageAssetPath: 'invalid-image-url.jpg',
    });

    render<App.Http.Data.HomePageProps>(<FrontPageNews />, {
      pageProps: {
        recentNews: [newsWithBrokenImage],
        ziggy: createZiggyProps({ device: 'desktop' }),
      },
    });

    // ACT
    const hiddenImage = screen.getByRole('img', { hidden: true });
    fireEvent.error(hiddenImage);

    // ASSERT
    const fallbackImage = screen.getByTestId('fallback-image');
    expect(fallbackImage).toBeVisible();
    expect(fallbackImage).toHaveAttribute('src', '/assets/images/ra-icon.webp');
  });

  it('given the news image is valid, displays it', () => {
    // ARRANGE
    const validImageUrl = 'valid-image.jpg';
    const newsWithValidImage = createNews({
      imageAssetPath: validImageUrl,
    });

    render<App.Http.Data.HomePageProps>(<FrontPageNews />, {
      pageProps: {
        recentNews: [newsWithValidImage],
        ziggy: createZiggyProps({ device: 'desktop' }),
      },
    });

    // ASSERT
    const imageEl = screen.getByRole('img', { hidden: true });

    expect(imageEl).toHaveAttribute('src', validImageUrl);
  });

  it('strips emoji from title and lead text', () => {
    // ARRANGE
    const newsWithEmoji = createNews({
      title: '🎮 Gaming News! 🎲',
      body: '🎯 Big tournament announced! 🏆',
    });

    render<App.Http.Data.HomePageProps>(<FrontPageNews />, {
      pageProps: {
        recentNews: [newsWithEmoji],
        ziggy: createZiggyProps({ device: 'desktop' }),
      },
    });

    // ASSERT
    expect(screen.getByText('Gaming News!')).toBeVisible();
    expect(screen.getByText('Big tournament announced!')).toBeVisible();

    expect(screen.queryByText('🎮')).not.toBeInTheDocument();
    expect(screen.queryByText('🎯')).not.toBeInTheDocument();
  });

  it('strips HTML tags from the lead text', () => {
    // ARRANGE
    const newsWithHtml = createNews({
      body: 'Click <a href="#">here</a> for more info!<br>New line content',
    });

    render<App.Http.Data.HomePageProps>(<FrontPageNews />, {
      pageProps: {
        recentNews: [newsWithHtml],
        ziggy: createZiggyProps({ device: 'desktop' }),
      },
    });

    // ASSERT
    expect(screen.getByText('Click for more info!New line content')).toBeVisible();
  });

  it('still allows news posts that dont have associated links', () => {
    // ARRANGE
    const news = createNews({ link: null });

    render<App.Http.Data.HomePageProps>(<FrontPageNews />, {
      pageProps: {
        recentNews: [news],
        ziggy: createZiggyProps({ device: 'desktop' }),
      },
    });

    // ASSERT
    expect(screen.getByText(news.title)).toBeVisible();
  });

  it('still allows news posts that dont have associated images', () => {
    // ARRANGE
    const news = createNews({ imageAssetPath: null });

    render<App.Http.Data.HomePageProps>(<FrontPageNews />, {
      pageProps: {
        recentNews: [news],
        ziggy: createZiggyProps({ device: 'desktop' }),
      },
    });

    // ASSERT
    expect(screen.getByText(news.title)).toBeVisible();
    expect(screen.getByTestId('fallback-image')).toBeVisible();
  });

  it('given the user is using a mobile device, still shows news', () => {
    // ARRANGE
    const recentNews = [
      createNews({ title: 'Foo' }),
      createNews({ title: 'Bar' }),
      createNews({ title: 'Baz' }),
    ];

    render<App.Http.Data.HomePageProps>(<FrontPageNews />, {
      pageProps: {
        recentNews,
        ziggy: createZiggyProps({ device: 'mobile' }),
      },
    });

    // ASSERT
    expect(screen.getByText('Foo')).toBeVisible();
    expect(screen.getByText('Bar')).toBeVisible();
    expect(screen.getByText('Baz')).toBeVisible();
  });

  it('given a news post is not pinned, does not show a pinned indicator', () => {
    // ARRANGE
    const recentNews = [createNews({ title: 'Foo', pinnedAt: null })];

    render<App.Http.Data.HomePageProps>(<FrontPageNews />, {
      pageProps: {
        recentNews,
        ziggy: createZiggyProps({ device: 'desktop' }),
      },
    });

    // ASSERT
    expect(screen.queryByText(/pinned/i)).not.toBeInTheDocument();
  });

  it('given a news post is pinned, shows a pinned indicator', () => {
    // ARRANGE
    const recentNews = [createNews({ title: 'Foo', pinnedAt: faker.date.recent().toISOString() })];

    render<App.Http.Data.HomePageProps>(<FrontPageNews />, {
      pageProps: {
        recentNews,
        ziggy: createZiggyProps({ device: 'desktop' }),
      },
    });

    // ASSERT
    expect(screen.getByText(/pinned/i)).toBeVisible();
  });

  it('shows pinned indicators on mobile news posts', () => {
    // ARRANGE
    const recentNews = [createNews({ title: 'Foo', pinnedAt: faker.date.recent().toISOString() })];

    render<App.Http.Data.HomePageProps>(<FrontPageNews />, {
      pageProps: {
        recentNews,
        ziggy: createZiggyProps({ device: 'mobile' }),
      },
    });

    // ASSERT
    expect(screen.getByText(/pinned/i)).toBeVisible();
  });

  it('given a news post was made within the last 24 hours, shows a "new" label on desktop', () => {
    // ARRANGE
    const recentNews = [
      createNews({ title: 'Foo', createdAt: dayjs.utc().subtract(1, 'hour').toISOString() }),
    ];

    render<App.Http.Data.HomePageProps>(<FrontPageNews />, {
      pageProps: {
        recentNews,
        ziggy: createZiggyProps({ device: 'desktop' }),
      },
    });

    // ASSERT
    expect(screen.getByText('new')).toBeVisible();
  });

  it('given a news post was not made within the last 24 hours, does not show a "new" label on desktop', () => {
    // ARRANGE
    const recentNews = [
      createNews({ title: 'Foo', createdAt: dayjs.utc().subtract(2, 'days').toISOString() }),
    ];

    render<App.Http.Data.HomePageProps>(<FrontPageNews />, {
      pageProps: {
        recentNews,
        ziggy: createZiggyProps({ device: 'desktop' }),
      },
    });

    // ASSERT
    expect(screen.queryByText('new')).not.toBeInTheDocument();
  });
});
