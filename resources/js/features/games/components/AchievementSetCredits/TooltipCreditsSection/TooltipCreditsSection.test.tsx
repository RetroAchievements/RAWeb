import { render, screen } from '@/test';
import type { TranslatedString } from '@/types/i18next';

import { TooltipCreditsSection } from './TooltipCreditsSection';

describe('Component: TooltipCreditsSection', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <TooltipCreditsSection headingLabel={'Test Heading' as TranslatedString}>
        <div>Test content</div>
      </TooltipCreditsSection>,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given a heading label, displays it with bold styling', () => {
    // ARRANGE
    render(
      <TooltipCreditsSection headingLabel={'Contributors' as TranslatedString}>
        <div>Test content</div>
      </TooltipCreditsSection>,
    );

    // ASSERT
    const heading = screen.getByText(/contributors/i);
    expect(heading).toBeVisible();
    expect(heading).toHaveClass('font-bold');
  });

  it('given children content, renders the children', () => {
    // ARRANGE
    render(
      <TooltipCreditsSection headingLabel={'Test Heading' as TranslatedString}>
        <div>First child</div>
        <div>Second child</div>
      </TooltipCreditsSection>,
    );

    // ASSERT
    expect(screen.getByText(/first child/i)).toBeVisible();
    expect(screen.getByText(/second child/i)).toBeVisible();
  });
});
