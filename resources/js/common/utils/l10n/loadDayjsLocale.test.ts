import dayjs from 'dayjs';

import { loadDayjsLocale } from './loadDayjsLocale';

describe('Util: loadDayjsLocale', () => {
  it('is defined', () => {
    // ASSERT
    expect(loadDayjsLocale).toBeDefined();
  });

  it('given the user locale is pt_BR, loads and sets the pt_BR locale successfully', async () => {
    // ARRANGE
    vi.spyOn(dayjs, 'locale').mockImplementationOnce(vi.fn());

    // ACT
    await loadDayjsLocale('pt_BR');

    // ASSERT
    expect(dayjs.locale).toHaveBeenCalledWith('pt-br');
  });
});
