import { render, screen } from '@/test';
import { createGame, createRaEvent } from '@/test/factories';

import { BoxArtImage } from './BoxArtImage';

describe('Component: BoxArtImage', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const event = createRaEvent({
      legacyGame: createGame({
        imageBoxArtUrl: 'https://example.com/image.jpg',
      }),
    });

    const { container } = render(<BoxArtImage event={event} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there is a valid box art URL, renders the image', () => {
    // ARRANGE
    const imageUrl = 'https://example.com/boxart.jpg';
    const event = createRaEvent({
      legacyGame: createGame({
        imageBoxArtUrl: imageUrl,
      }),
    });

    render(<BoxArtImage event={event} />);

    // ASSERT
    const imgElement = screen.getByRole('img', { name: /boxart/i });
    expect(imgElement).toBeVisible();
    expect(imgElement).toHaveAttribute('src', imageUrl);
  });

  it('given there is no box art URL, renders nothing', () => {
    // ARRANGE
    const event = createRaEvent({
      legacyGame: createGame({
        imageBoxArtUrl: undefined,
      }),
    });

    render(<BoxArtImage event={event} />);

    // ASSERT
    expect(screen.queryByRole('img')).not.toBeInTheDocument();
  });

  it('given the box art URL contains "000002", renders nothing', () => {
    // ARRANGE
    const event = createRaEvent({
      legacyGame: createGame({
        imageBoxArtUrl: undefined,
      }),
    });

    render(<BoxArtImage event={event} />);

    // ASSERT
    expect(screen.queryByRole('img')).not.toBeInTheDocument();
  });

  it('given there is no legacy game data, renders nothing', () => {
    // ARRANGE
    const event = createRaEvent({
      legacyGame: undefined,
    });

    render(<BoxArtImage event={event} />);

    // ASSERT
    expect(screen.queryByRole('img')).not.toBeInTheDocument();
  });
});
