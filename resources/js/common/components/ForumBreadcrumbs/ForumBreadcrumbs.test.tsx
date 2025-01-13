import i18n from '@/i18n-client';
import { render, screen } from '@/test';
import { createForum, createForumCategory, createForumTopic } from '@/test/factories';

import { ForumBreadcrumbs } from './ForumBreadcrumbs';

describe('Component: ForumBreadcrumbs', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<ForumBreadcrumbs t_currentPageLabel={i18n.t('Recent Posts')} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('has a link back to the forum index', () => {
    // ARRANGE
    render(<ForumBreadcrumbs t_currentPageLabel={i18n.t('Recent Posts')} />);

    // ASSERT
    const forumIndexLinkEl = screen.getByRole('link', { name: /forum index/i });
    expect(forumIndexLinkEl).toBeVisible();
    expect(forumIndexLinkEl).toHaveAttribute('href', '/forum.php');
  });

  it('communicates the active link in an accessible manner', () => {
    // ARRANGE
    render(<ForumBreadcrumbs t_currentPageLabel={i18n.t('Recent Posts')} />);

    // ASSERT
    const activeLinkEl = screen.getByRole('link', { name: /recent posts/i });

    expect(activeLinkEl).toBeVisible();
    expect(activeLinkEl).toHaveAttribute('aria-disabled', 'true');
    expect(activeLinkEl).toHaveAttribute('aria-current', 'page');
  });

  it('given a category, has a link to the category', () => {
    // ARRANGE
    const category = createForumCategory({ title: 'Category' });

    render(<ForumBreadcrumbs forumCategory={category} t_currentPageLabel={i18n.t('Edit Post')} />);

    // ASSERT
    const categoryEl = screen.getByRole('link', { name: /category/i });
    expect(categoryEl).toBeVisible();
    expect(categoryEl).toHaveAttribute('href', `/forum.php?c=${category.id}`);
  });

  it('given a forum, has a link to the forum', () => {
    // ARRANGE
    const forum = createForum({ title: 'Chit Chat' });

    render(<ForumBreadcrumbs forum={forum} t_currentPageLabel={i18n.t('Edit Post')} />);

    // ASSERT
    const forumEl = screen.getByRole('link', { name: /chit chat/i });
    expect(forumEl).toBeVisible();
    expect(forumEl).toHaveAttribute('href', `/viewforum.php?f=${forum.id}`);
  });

  it('given a topic, has a link to the topic', () => {
    // ARRANGE
    const topic = createForumTopic({ title: 'Dragon Quest III' });

    render(<ForumBreadcrumbs forumTopic={topic} t_currentPageLabel={i18n.t('Edit Post')} />);

    // ASSERT
    const topicEl = screen.getByRole('link', { name: /dragon quest iii/i });
    expect(topicEl).toBeVisible();
    expect(topicEl).toHaveAttribute('href', `/viewtopic.php?t=${topic.id}`);
  });
});
