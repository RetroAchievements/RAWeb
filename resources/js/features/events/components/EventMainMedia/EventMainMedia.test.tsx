import userEvent from '@testing-library/user-event';

import { render, screen, waitFor } from '@/test';

import { EventMainMedia } from './EventMainMedia';

describe('Component: EventMainMedia', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <EventMainMedia
        imageTitleUrl="https://example.com/title.jpg"
        imageIngameUrl="https://example.com/ingame.jpg"
      />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given both images are the default "No Screenshot Found" image, renders nothing', () => {
    // ARRANGE
    render(
      <EventMainMedia imageTitleUrl="something/000002.jpg" imageIngameUrl="something/000002.jpg" />,
    );

    // ASSERT
    expect(screen.queryAllByRole('img')).toHaveLength(0);
  });

  it('given valid image URLs, displays both images', () => {
    // ARRANGE
    render(
      <EventMainMedia
        imageTitleUrl="https://example.com/title.jpg"
        imageIngameUrl="https://example.com/ingame.jpg"
      />,
    );

    // ASSERT
    const images = screen.getAllByRole('img');
    expect(images).toHaveLength(2);
    expect(images[0]).toHaveAttribute('src', 'https://example.com/title.jpg');
    expect(images[1]).toHaveAttribute('src', 'https://example.com/ingame.jpg');
  });

  it('given the user clicks a screenshot, opens it in a dialog', async () => {
    // ARRANGE
    render(
      <EventMainMedia
        imageTitleUrl="https://example.com/title.jpg"
        imageIngameUrl="https://example.com/ingame.jpg"
      />,
    );

    // ACT
    const titleImage = screen.getAllByRole('img')[0];
    await userEvent.click(titleImage);

    // ASSERT
    await waitFor(() => {
      expect(screen.getByRole('dialog')).toBeVisible();
    });
  });
});
