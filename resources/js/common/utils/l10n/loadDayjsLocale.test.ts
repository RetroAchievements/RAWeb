import dayjs from 'dayjs';

import { loadDayjsLocale } from './loadDayjsLocale';

describe('Util: loadDayjsLocale', () => {
  it('is defined', () => {
    // ASSERT
    expect(loadDayjsLocale).toBeDefined();
  });

  it('given the locale is en_US, does not log a warning', async () => {
    // ARRANGE
    vi.spyOn(console, 'warn').mockImplementation(() => {});
    const localeSpy = vi.spyOn(dayjs, 'locale');

    // ACT
    await loadDayjsLocale('en_US');

    // ASSERT
    expect(localeSpy).not.toHaveBeenCalled();
    expect(console.warn).not.toHaveBeenCalled();
  });

  it.each`
    userLocale | dayjsLocale
    ${'pt_BR'} | ${'pt-br'}
    ${'pl_PL'} | ${'pl'}
    ${'es_ES'} | ${'es'}
  `('loads and sets the $userLocale locale successfully', async ({ userLocale, dayjsLocale }) => {
    // ARRANGE
    vi.spyOn(console, 'warn').mockImplementation(() => {});
    const localeSpy = vi.spyOn(dayjs, 'locale');

    // ACT
    await loadDayjsLocale(userLocale);

    // ASSERT
    expect(localeSpy).toHaveBeenCalledWith(dayjsLocale);
    expect(console.warn).not.toHaveBeenCalled();
  });

  it('when given an unsupported locale, does nothing', async () => {
    // ARRANGE
    vi.spyOn(console, 'warn').mockImplementation(() => {});
    vi.spyOn(dayjs, 'locale').mockImplementation(vi.fn());

    // ACT
    await loadDayjsLocale('klingon');

    // ASSERT
    expect(dayjs.locale).not.toHaveBeenCalled();
  });

  it('logs a warning if an error occurs while loading a locale', async () => {
    // ARRANGE
    vi.spyOn(console, 'warn').mockImplementation(() => {});
    vi.spyOn(dayjs, 'locale').mockImplementation(vi.fn());

    // Temporarily mock the dynamic import to throw an error.
    vi.doMock('dayjs/locale/pt-br.js', () => {
      throw new Error('Import failed');
    });

    // ACT
    await loadDayjsLocale('pt_BR');

    // ASSERT
    expect(console.warn).toHaveBeenCalledWith(
      expect.stringContaining('Unable to load Day.js locale for pt_BR'),
      expect.any(Error),
    );
  });
});
