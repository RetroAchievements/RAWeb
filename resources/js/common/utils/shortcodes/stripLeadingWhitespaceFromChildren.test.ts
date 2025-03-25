import { stripLeadingWhitespaceFromChildren } from './stripLeadingWhitespaceFromChildren';

describe('Function: stripLeadingWhitespaceFromChildren', () => {
  it('is defined', () => {
    // ASSERT
    expect(stripLeadingWhitespaceFromChildren).toBeDefined();
  });

  it('invokes without crashing', () => {
    // ARRANGE
    const input = 'test';

    // ACT
    const result = stripLeadingWhitespaceFromChildren(input);

    // ASSERT
    expect(result).toBeTruthy();
  });

  it('given a single string with no whitespace, returns it unchanged', () => {
    // ARRANGE
    const input = 'hello world';

    // ACT
    const result = stripLeadingWhitespaceFromChildren(input);

    // ASSERT
    expect(result).toEqual(['hello world']);
  });

  it('given leading empty strings, removes them', () => {
    // ARRANGE
    const input = ['', '  ', 'hello world'];

    // ACT
    const result = stripLeadingWhitespaceFromChildren(input);

    // ASSERT
    expect(result).toEqual(['hello world']);
  });

  it('given leading br elements, removes them', () => {
    // ARRANGE
    const input = [{ type: 'br' }, { type: 'br' }, 'hello world'];

    // ACT
    const result = stripLeadingWhitespaceFromChildren(input as any);

    // ASSERT
    expect(result).toEqual(['hello world']);
  });

  it('given leading empty objects, removes them', () => {
    // ARRANGE
    const input = [{}, {}, 'hello world'];

    // ACT
    const result = stripLeadingWhitespaceFromChildren(input as any);

    // ASSERT
    expect(result).toEqual(['hello world']);
  });

  it('given mixed leading whitespace elements, removes them all', () => {
    // ARRANGE
    const input = ['', {}, { type: 'br' }, '  ', 'hello world'];

    // ACT
    const result = stripLeadingWhitespaceFromChildren(input as any);

    // ASSERT
    expect(result).toEqual(['hello world']);
  });

  it('given whitespace elements after content, preserves them', () => {
    // ARRANGE
    const input = ['hello', '', {}, { type: 'br' }, 'world'];

    // ACT
    const result = stripLeadingWhitespaceFromChildren(input as any);

    // ASSERT
    expect(result).toEqual(['hello', '', {}, { type: 'br' }, 'world']);
  });

  it('given only whitespace elements, returns an empty array', () => {
    // ARRANGE
    const input = ['', {}, { type: 'br' }, '  '];

    // ACT
    const result = stripLeadingWhitespaceFromChildren(input as any);

    // ASSERT
    expect(result).toEqual([]);
  });
});
