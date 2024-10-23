import { render, screen } from '@/test';

import { FrontPageNews } from './FrontPageNews';

describe('Component: FrontPageNews', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<FrontPageNews />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays an accessible heading', () => {
    // ARRANGE
    render(<FrontPageNews />);

    // ASSERT
    expect(screen.getByRole('heading', { name: /news/i })).toBeVisible();
  });

  it.todo('displays news article headings');
  it.todo('displays news article lead text');
  it.todo('displays a timestamp and label for each article post and author');
  it.todo('accessibly links each post to the correct URL');
  it.todo('has a link to the news archive');
  it.todo('has click tracking metadata attached on each news post');
});
