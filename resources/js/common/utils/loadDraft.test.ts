import { loadDraft } from './loadDraft';

describe('Util: loadDraft', () => {
  it('is defined', () => {
    // ASSERT
    expect(loadDraft).toBeDefined();
  });

  it('given there is no saved draft, returns an empty object', () => {
    // ACT
    const result = loadDraft('nonexistent-key');

    // ASSERT
    expect(result).toEqual({});
  });

  it('given there is a saved draft, returns the parsed values', () => {
    // ARRANGE
    sessionStorage.setItem('test-draft', JSON.stringify({ title: 'Hello', body: 'World' }));

    // ACT
    const result = loadDraft('test-draft');

    // ASSERT
    expect(result).toEqual({ title: 'Hello', body: 'World' });
  });

  it('given the stored value is corrupted JSON, returns an empty object', () => {
    // ARRANGE
    sessionStorage.setItem('bad-draft', '{not valid json');

    // ACT
    const result = loadDraft('bad-draft');

    // ASSERT
    expect(result).toEqual({});
  });
});
