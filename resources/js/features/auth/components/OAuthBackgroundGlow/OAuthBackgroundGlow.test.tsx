import { render, screen } from '@/test';

import { OAuthBackgroundGlow } from './OAuthBackgroundGlow';

describe('Component: OAuthBackgroundGlow', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<OAuthBackgroundGlow />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given no variant prop, uses the default blue color', () => {
    // ARRANGE
    render(<OAuthBackgroundGlow />);

    // ASSERT
    const glowDiv = screen.getByTestId('glow');
    expect(glowDiv).toHaveStyle({
      background: expect.stringContaining('rgba(59, 130, 246'),
    });
  });

  it('given the variant is success, uses the green color', () => {
    // ARRANGE
    render(<OAuthBackgroundGlow variant="success" />);

    // ASSERT
    const glowDiv = screen.getByTestId('glow');
    expect(glowDiv).toHaveStyle({
      background: expect.stringContaining('rgba(34, 197, 94'),
    });
  });

  it('given the variant is error, uses the red color', () => {
    // ARRANGE
    render(<OAuthBackgroundGlow variant="error" initial={{ opacity: 0, scale: 0.8 }} />);

    // ASSERT
    const glowDiv = screen.getByTestId('glow');
    expect(glowDiv).toHaveStyle({
      background: expect.stringContaining('rgba(239, 68, 68'),
    });
  });

  it('given the variant is default, uses the blue color', () => {
    // ARRANGE
    render(<OAuthBackgroundGlow variant="default" initial={false} />);

    // ASSERT
    const glowDiv = screen.getByTestId('glow');
    expect(glowDiv).toHaveStyle({
      background: expect.stringContaining('rgba(59, 130, 246'),
    });
  });
});
