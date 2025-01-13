import { convertYouTubeTime } from './convertYouTubeTime';

describe('Util: convertYouTubeTime', () => {
  it('is defined', () => {
    // ASSERT
    expect(convertYouTubeTime).toBeDefined();
  });

  it('given the input is already in seconds, returns that number of seconds', () => {
    // ACT
    const result = convertYouTubeTime('45');

    // ASSERT
    expect(result).toEqual(45);
  });

  it('given the input contains only seconds, converts to seconds', () => {
    // ACT
    const result = convertYouTubeTime('45s');

    // ASSERT
    expect(result).toEqual(45);
  });

  it('given the input contains minutes and seconds, converts to total seconds', () => {
    // ACT
    const result = convertYouTubeTime('2m30s');

    // ASSERT
    expect(result).toEqual(150); // (2 * 60) + 30
  });

  it('given the input contains hours, minutes, and seconds, converts to total seconds', () => {
    // ACT
    const result = convertYouTubeTime('1h30m15s');

    // ASSERT
    expect(result).toEqual(5415);
  });

  it('given the input contains only minutes, converts to total seconds', () => {
    // ACT
    const result = convertYouTubeTime('90m');

    // ASSERT
    expect(result).toEqual(5400); // 90 * 60
  });

  it('given the input is an invalid format, returns 0', () => {
    // ACT
    const result = convertYouTubeTime('invalid');

    // ASSERT
    expect(result).toEqual(0);
  });
});
