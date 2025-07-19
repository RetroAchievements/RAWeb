import { render, screen } from '@/test';
import type { TranslatedString } from '@/types/i18next';

import { PlayableSidebarButtonsSection } from './PlayableSidebarButtonsSection';

describe('Component: PlayableSidebarButtonsSection', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <PlayableSidebarButtonsSection headingLabel={'Test Heading' as TranslatedString}>
        <div>Test Child</div>
      </PlayableSidebarButtonsSection>,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given a heading label, displays it', () => {
    // ARRANGE
    render(
      <PlayableSidebarButtonsSection headingLabel={'My Section Title' as TranslatedString}>
        <div>Test Child</div>
      </PlayableSidebarButtonsSection>,
    );

    // ASSERT
    expect(screen.getByText(/my section title/i)).toBeVisible();
  });

  it('given children elements, renders them', () => {
    // ARRANGE
    render(
      <PlayableSidebarButtonsSection headingLabel={'Test Heading' as TranslatedString}>
        <button>Click me</button>
        <button>Another button</button>
      </PlayableSidebarButtonsSection>,
    );

    // ASSERT
    expect(screen.getByRole('button', { name: /click me/i })).toBeVisible();
    expect(screen.getByRole('button', { name: /another button/i })).toBeVisible();
  });
});
