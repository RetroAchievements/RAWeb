import { createAuthenticatedUser } from '@/common/models';
import { render, screen } from '@/test';

import { TemplateKindAlert } from './TemplateKindAlert';

describe('Component: TemplateKindAlert', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<TemplateKindAlert templateKind="manual-unlock" />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the user has a non-English locale, shows the English requirement message', () => {
    // ARRANGE
    render(<TemplateKindAlert templateKind="manual-unlock" />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser({ locale: 'es_ES' }),
        },
      },
    });

    // ASSERT
    expect(screen.getByText(/please write your message in english/i)).toBeVisible(); // assert message is in English due to the test env
  });

  it('given the user has an English locale, does not show the English requirement message', () => {
    // ARRANGE
    render(<TemplateKindAlert templateKind="manual-unlock" />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser({ locale: 'en_US' }),
        },
      },
    });

    // ASSERT
    expect(screen.queryByText(/please write your message in english/i)).not.toBeInTheDocument();
  });

  it('given the template kind is manual-unlock, shows the manual unlock specific messages', () => {
    // ARRANGE
    render(<TemplateKindAlert templateKind="manual-unlock" />);

    // ASSERT
    expect(screen.getByText(/provide as much evidence as possible/i)).toBeVisible();
    expect(screen.getByText(/person reviewing the request will almost always want/i)).toBeVisible();
  });

  it('given the template kind is unwelcome-concept, shows the unwelcome concept specific messages', () => {
    // ARRANGE
    render(<TemplateKindAlert templateKind="unwelcome-concept" />);

    // ASSERT
    expect(screen.getByText(/follow the template below/i)).toBeVisible();
    expect(screen.getByText(/consult the docs/i)).toBeVisible();
  });

  it('given the template kind is neither manual-unlock nor unwelcome-concept, shows the default message', () => {
    // ARRANGE
    render(<TemplateKindAlert templateKind="misclassification" />);

    // ASSERT
    expect(screen.getByText(/provide as much information as possible/i)).toBeVisible();
    expect(screen.queryByText(/follow the template below/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/provide as much evidence as possible/i)).not.toBeInTheDocument();
  });
});
