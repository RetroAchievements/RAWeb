import { render, screen } from '@/test';

import { OAuthPageLayout } from './OAuthPageLayout';

// Shallow render the children.
vi.mock('../OAuthBackgroundGlow', () => ({
  OAuthBackgroundGlow: ({ variant, initial }: any) => (
    <div data-testid="oauth-background-glow" data-variant={variant} data-initial={initial} />
  ),
}));
vi.mock('../OAuthBranding', () => ({
  OAuthBranding: ({ initial }: any) => <div data-testid="oauth-branding" data-initial={initial} />,
}));

describe('Component: OAuthPageLayout', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <OAuthPageLayout>
        <div>Test content</div>
      </OAuthPageLayout>,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('renders the children content', () => {
    // ARRANGE
    render(
      <OAuthPageLayout>
        <div>Test child content</div>
      </OAuthPageLayout>,
    );

    // ASSERT
    expect(screen.getByText(/test child content/i)).toBeVisible();
  });
});
