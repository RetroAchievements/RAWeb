import { renderHook } from '@/test';

import { useCleanBreadcrumbHubTitles } from './useCleanBreadcrumbHubTitles';

describe('Hook: useCleanBreadcrumbHubTitles', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { result } = renderHook(() => useCleanBreadcrumbHubTitles());

    // ASSERT
    expect(result.current).toBeDefined();
  });

  describe('cleanBreadcrumbHubTitles', () => {
    it('given the title equals "[Central]" returns "All Hubs"', () => {
      // ARRANGE
      const { result } = renderHook(() => useCleanBreadcrumbHubTitles());

      // ACT
      const cleaned = result.current.cleanBreadcrumbHubTitles('[Central]', new Set(), null);

      // ASSERT
      expect(cleaned).toEqual('All Hubs');
    });

    it('given the title equals "Central", returns "All Hubs"', () => {
      // ARRANGE
      const { result } = renderHook(() => useCleanBreadcrumbHubTitles());

      // ACT
      const cleaned = result.current.cleanBreadcrumbHubTitles('Central', new Set(), null);

      // ASSERT
      expect(cleaned).toEqual('All Hubs');
    });

    it('given a DevQuest title, formats as "N: Title"', () => {
      // ARRANGE
      const { result } = renderHook(() => useCleanBreadcrumbHubTitles());

      // ACT
      const cleaned = result.current.cleanBreadcrumbHubTitles(
        '[DevQuest 001 Sets] Some Content',
        new Set(),
        null,
      );

      // ASSERT
      expect(cleaned).toEqual('1: Some Content');
    });

    it('given a DevQuest title with leading zeros, strips the leading zeros', () => {
      // ARRANGE
      const { result } = renderHook(() => useCleanBreadcrumbHubTitles());

      // ACT
      const cleaned = result.current.cleanBreadcrumbHubTitles(
        '[DevQuest 021 Sets] Homebrew Heaven',
        new Set(),
        null,
      );

      // ASSERT
      expect(cleaned).toEqual('21: Homebrew Heaven');
    });

    it('given a DevQuest title without a parseable number, returns only the content', () => {
      // ARRANGE
      const { result } = renderHook(() => useCleanBreadcrumbHubTitles());

      // ACT
      const cleaned = result.current.cleanBreadcrumbHubTitles(
        '[DevQuestX Sets] Some Content',
        new Set(),
        null,
      );

      // ASSERT
      expect(cleaned).toEqual('Some Content');
    });

    it('given a title with an organizational prefix, strips the prefix', () => {
      // ARRANGE
      const { result } = renderHook(() => useCleanBreadcrumbHubTitles());

      // ACT
      const cleaned = result.current.cleanBreadcrumbHubTitles(
        '[ASB - Some Title]',
        new Set(),
        null,
      );

      // ASSERT
      expect(cleaned).toEqual('Some Title');
    });

    it('given a single word title, returns it as-is', () => {
      // ARRANGE
      const { result } = renderHook(() => useCleanBreadcrumbHubTitles());

      // ACT
      const cleaned = result.current.cleanBreadcrumbHubTitles('[SingleWord]', new Set(), null);

      // ASSERT
      expect(cleaned).toEqual('SingleWord');
    });

    it('given the first part of the title matches the parent, removes the duplicative part', () => {
      // ARRANGE
      const { result } = renderHook(() => useCleanBreadcrumbHubTitles());

      // ACT
      const cleaned = result.current.cleanBreadcrumbHubTitles(
        '[Genre - Action]',
        new Set(),
        '[Parent - Genre]',
      );

      // ASSERT
      expect(cleaned).toEqual('Action');
    });

    it('given a prefix has already been seen, strips it from subsequent titles', () => {
      // ARRANGE
      const { result } = renderHook(() => useCleanBreadcrumbHubTitles());
      const seenPrefixes = new Set<string>();

      // ASSERT
      const firstCleaned = result.current.cleanBreadcrumbHubTitles(
        '[Series - First Game]',
        seenPrefixes,
        null,
      );
      expect(firstCleaned).toEqual('Series - First Game');

      const secondCleaned = result.current.cleanBreadcrumbHubTitles(
        '[Series - Second Game]',
        seenPrefixes,
        null,
      );
      expect(secondCleaned).toEqual('Second Game');
    });

    it('given a DevQuest title with extra whitespace, trims it', () => {
      // ARRANGE
      const { result } = renderHook(() => useCleanBreadcrumbHubTitles());

      // ACT
      const cleaned = result.current.cleanBreadcrumbHubTitles(
        '[DevQuest 015 Sets]   Content With Spaces  ',
        new Set(),
        null,
      );

      // ASSERT
      expect(cleaned).toEqual('15: Content With Spaces');
    });
  });
});
