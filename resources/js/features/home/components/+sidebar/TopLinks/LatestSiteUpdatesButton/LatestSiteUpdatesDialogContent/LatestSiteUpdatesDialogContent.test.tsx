import { BaseDialog } from '@/common/components/+vendor/BaseDialog';
import { render, screen } from '@/test';
import { createNews } from '@/test/factories';

import { LatestSiteUpdatesDialogContent } from './LatestSiteUpdatesDialogContent';

describe('Component: LatestSiteUpdatesDialogContent', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <BaseDialog open={true}>
        <LatestSiteUpdatesDialogContent />
      </BaseDialog>,
      {
        pageProps: {
          auth: null,
          deferredSiteReleaseNotes: [],
          hasUnreadSiteReleaseNote: false,
        },
      },
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given no release notes are loaded yet, displays a loading state', () => {
    // ARRANGE
    render(
      <BaseDialog open={true}>
        <LatestSiteUpdatesDialogContent />
      </BaseDialog>,
      {
        pageProps: {
          auth: null,
          deferredSiteReleaseNotes: null,
          hasUnreadSiteReleaseNote: false,
        },
      },
    );

    // ASSERT
    expect(screen.getByText(/loading/i)).toBeVisible();
  });

  it('given release notes are available, displays them', () => {
    // ARRANGE
    render(
      <BaseDialog open={true}>
        <LatestSiteUpdatesDialogContent />
      </BaseDialog>,
      {
        pageProps: {
          auth: null,
          deferredSiteReleaseNotes: [
            createNews({
              title: 'New Feature Release',
              body: 'We have added a new feature.',
              link: null,
            }),
          ],
          hasUnreadSiteReleaseNote: false,
        },
      },
    );

    // ASSERT
    expect(screen.getByText(/new feature release/i)).toBeVisible();
    expect(screen.getByText(/we have added a new feature/i)).toBeVisible();
  });

  it('given a release note has a link, displays the full release notes link', () => {
    // ARRANGE
    render(
      <BaseDialog open={true}>
        <LatestSiteUpdatesDialogContent />
      </BaseDialog>,
      {
        pageProps: {
          auth: null,
          deferredSiteReleaseNotes: [
            createNews({
              title: 'Major Update',
              body: 'See the full notes for details.',
              link: 'https://example.com/release-notes', // !!
            }),
          ],
          hasUnreadSiteReleaseNote: false,
        },
      },
    );

    // ASSERT
    const link = screen.getByRole('link', { name: /see full release notes/i });
    expect(link).toBeVisible();
    expect(link).toHaveAttribute('href', 'https://example.com/release-notes');
    expect(link).toHaveAttribute('target', '_blank');
  });

  it('given a release note has no link, does not display the full release notes link', () => {
    // ARRANGE
    render(
      <BaseDialog open={true}>
        <LatestSiteUpdatesDialogContent />
      </BaseDialog>,
      {
        pageProps: {
          auth: null,
          deferredSiteReleaseNotes: [
            createNews({
              title: 'Minor Update',
              body: 'Small improvements.',
              link: null, // !!
            }),
          ],
          hasUnreadSiteReleaseNote: false,
        },
      },
    );

    // ASSERT
    expect(screen.queryByRole('link', { name: /see full release notes/i })).not.toBeInTheDocument();
  });
});
