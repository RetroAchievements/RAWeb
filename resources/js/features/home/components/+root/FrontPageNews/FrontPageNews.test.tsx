import { fireEvent, render, screen } from '@/test';
import { createHomePageProps, createNews } from '@/test/factories';

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
      pageProps: createHomePageProps(),
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
      },
    });

    // ASSERT
    expect(screen.getByText(recentNews[0].payload)).toBeVisible();
    expect(screen.getByText(recentNews[1].payload)).toBeVisible();
    expect(screen.getByText(recentNews[2].payload)).toBeVisible();
  });

  it('given the news image fails to load, shows the fallback', () => {
    // ARRANGE
    const newsWithBrokenImage = createNews({
      image: 'invalid-image-url.jpg',
    });

    render<App.Http.Data.HomePageProps>(<FrontPageNews />, {
      pageProps: {
        recentNews: [newsWithBrokenImage],
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
      image: validImageUrl,
    });

    render<App.Http.Data.HomePageProps>(<FrontPageNews />, {
      pageProps: {
        recentNews: [newsWithValidImage],
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
      payload: '🎯 Big tournament announced! 🏆',
    });

    render<App.Http.Data.HomePageProps>(<FrontPageNews />, {
      pageProps: {
        recentNews: [newsWithEmoji],
      },
    });

    expect(screen.getByText('Gaming News!')).toBeVisible();
    expect(screen.getByText('Big tournament announced!')).toBeVisible();

    expect(screen.queryByText('🎮')).not.toBeInTheDocument();
    expect(screen.queryByText('🎯')).not.toBeInTheDocument();
  });

  it('strips HTML tags from the lead text', () => {
    // ARRANGE
    const newsWithHtml = createNews({
      payload: 'Click <a href="#">here</a> for more info!<br>New line content',
    });

    render<App.Http.Data.HomePageProps>(<FrontPageNews />, {
      pageProps: {
        recentNews: [newsWithHtml],
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
      },
    });

    // ASSERT
    expect(screen.getByText(news.title)).toBeVisible();
  });

  it('still allows news posts that dont have associated images', () => {
    // ARRANGE
    const news = createNews({ image: null });

    render<App.Http.Data.HomePageProps>(<FrontPageNews />, {
      pageProps: {
        recentNews: [news],
      },
    });

    // ASSERT
    expect(screen.getByText(news.title)).toBeVisible();
    expect(screen.getByTestId('fallback-image')).toBeVisible();
  });
});
