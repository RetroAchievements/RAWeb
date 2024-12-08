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
});
