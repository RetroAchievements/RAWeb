import { render, screen } from '@/test';

import { PlayableBoxArtImage } from './PlayableBoxArtImage';

describe('Component: PlayableBoxArtImage', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<PlayableBoxArtImage src="https://example.com/image.jpg" />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there is a valid box art URL, renders the image', () => {
    // ARRANGE
    render(<PlayableBoxArtImage src="https://example.com/image.jpg" />);

    // ASSERT
    const imgElement = screen.getByRole('img', { name: /boxart/i });
    expect(imgElement).toBeVisible();
    expect(imgElement).toHaveAttribute('src', 'https://example.com/image.jpg');
  });

  it('given there is no box art URL, renders nothing', () => {
    // ARRANGE
    render(<PlayableBoxArtImage src={undefined} />);

    // ASSERT
    expect(screen.queryByRole('img')).not.toBeInTheDocument();
  });

  it('given the box art URL contains "000002", renders nothing', () => {
    // ARRANGE
    render(<PlayableBoxArtImage src="https://example.com/000002.jpg" />);

    // ASSERT
    expect(screen.queryByRole('img')).not.toBeInTheDocument();
  });
});
