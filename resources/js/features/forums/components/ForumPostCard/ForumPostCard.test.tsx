import { render, screen } from '@/test';

import { ForumPostCard } from './ForumPostCard';

describe('Component: ForumPostCard', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<ForumPostCard body="Test content" />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given a post body, renders it correctly', () => {
    // ARRANGE
    const body = '[b]Breaking News[/b] this is a test case';
    render(<ForumPostCard body={body} />);

    // ASSERT
    const boldEl = screen.getByText(/breaking news/i);
    expect(boldEl.nodeName).toEqual('SPAN');
    expect(boldEl).toHaveStyle('font-weight: bold');

    expect(screen.getByText(/this is a test case/i)).toBeVisible();
  });
});
