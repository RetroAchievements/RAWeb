import { renderHook } from '@/test';

import { useShortcodesList } from './useShortcodesList';

describe('Hook: useShortcodesList', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { result } = renderHook(() => useShortcodesList());

    // ASSERT
    expect(result.current).toBeTruthy();
  });

  it('given the hook is invoked, returns a list of shortcodes with the expected structure', () => {
    // ARRANGE
    const { result } = renderHook(() => useShortcodesList());

    // ASSERT
    const { shortcodesList } = result.current;
    expect(shortcodesList).toEqual(
      expect.arrayContaining([
        expect.objectContaining({
          icon: expect.any(Function),
          t_label: expect.any(String),
          start: expect.any(String),
          end: expect.any(String),
        }),
      ]),
    );
  });

  it('given the hook is invoked, returns all expected shortcode types', () => {
    // ARRANGE
    const { result } = renderHook(() => useShortcodesList());

    // ASSERT
    const { shortcodesList } = result.current;
    const shortcodeTypes = shortcodesList.map((code) => code.start);

    expect(shortcodeTypes).toEqual(
      expect.arrayContaining([
        '[b]',
        '[i]',
        '[u]',
        '[s]',
        '[code]',
        '[spoiler]',
        '[img=',
        '[url=',
        '[ach=',
        '[game=',
        '[hub=',
        '[user=',
        '[ticket=',
      ]),
    );
  });
});
