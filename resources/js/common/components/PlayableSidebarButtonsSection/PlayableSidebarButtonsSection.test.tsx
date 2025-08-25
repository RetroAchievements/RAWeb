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

  it('given all children are null, returns null', () => {
    // ARRANGE
    render(
      <PlayableSidebarButtonsSection headingLabel={'Test Heading' as TranslatedString}>
        {null}
      </PlayableSidebarButtonsSection>,
    );

    // ASSERT
    expect(screen.queryByText(/test heading/i)).not.toBeInTheDocument();
  });

  it('given children is an array of nulls, returns null', () => {
    // ARRANGE
    render(
      <PlayableSidebarButtonsSection headingLabel={'Test Heading' as TranslatedString}>
        {null}
        {null}
      </PlayableSidebarButtonsSection>,
    );

    // ASSERT
    expect(screen.queryByText(/test heading/i)).not.toBeInTheDocument();
  });

  it('given at least one visible child among nulls, renders the section', () => {
    // ARRANGE
    render(
      <PlayableSidebarButtonsSection headingLabel={'Test Heading' as TranslatedString}>
        {null}
        <button>Visible button</button>
      </PlayableSidebarButtonsSection>,
    );

    // ASSERT
    expect(screen.getByText(/test heading/i)).toBeVisible();
    expect(screen.getByRole('button', { name: /visible button/i })).toBeVisible();
    expect(screen.queryByRole('button', { name: /hidden/i })).not.toBeInTheDocument();
  });
});
