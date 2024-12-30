import { cleanHubTitle } from './cleanHubTitle';

describe('Util: cleanHubTitle', () => {
  it('is defined', () => {
    // ASSERT
    expect(cleanHubTitle).toBeDefined();
  });

  it('given a title with surrounding brackets, removes them', () => {
    // ARRANGE
    const title = '[Test Hub]';

    // ACT
    const result = cleanHubTitle(title);

    // ASSERT
    expect(result).toEqual('Test Hub');
  });

  it('given a title without brackets, returns it unchanged', () => {
    // ARRANGE
    const title = 'Test Hub';

    // ACT
    const result = cleanHubTitle(title);

    // ASSERT
    expect(result).toEqual('Test Hub');
  });

  it('given a title with whitespace around brackets, trims and removes brackets', () => {
    // ARRANGE
    const title = '  [Test Hub]  ';

    // ACT
    const result = cleanHubTitle(title);

    // ASSERT
    expect(result).toEqual('Test Hub');
  });

  it('given a title with a prefix and shouldRemovePrefix is true, removes the prefix', () => {
    // ARRANGE
    const title = '[Central - Series]';

    // ACT
    const result = cleanHubTitle(title, true);

    // ASSERT
    expect(result).toEqual('Series');
  });

  it('given a title with multiple dashes and shouldRemovePrefix is true, only removes the first segment', () => {
    // ARRANGE
    const title = '[Central - Series - Extra]';

    // ACT
    const result = cleanHubTitle(title, true);

    // ASSERT
    expect(result).toEqual('Series - Extra');
  });

  it('given a title without a prefix and shouldRemovePrefix is true, returns the title unchanged', () => {
    // ARRANGE
    const title = '[Series]';

    // ACT
    const result = cleanHubTitle(title, true);

    // ASSERT
    expect(result).toEqual('Series');
  });
});
