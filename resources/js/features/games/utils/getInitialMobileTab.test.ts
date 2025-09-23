import { getInitialMobileTab } from './getInitialMobileTab';

describe('Util: getInitialMobileTab', () => {
  it('is defined', () => {
    // ASSERT
    expect(getInitialMobileTab).toBeDefined();
  });

  it('given no tab query param is provided, returns achievements', () => {
    // ACT
    const result = getInitialMobileTab();

    // ASSERT
    expect(result).toEqual('achievements');
  });

  it('given achievements is provided, returns achievements', () => {
    // ACT
    const result = getInitialMobileTab('achievements');

    // ASSERT
    expect(result).toEqual('achievements');
  });

  it('given community is provided, returns community', () => {
    // ACT
    const result = getInitialMobileTab('community');

    // ASSERT
    expect(result).toEqual('community');
  });

  it('given info is provided, returns info', () => {
    // ACT
    const result = getInitialMobileTab('info');

    // ASSERT
    expect(result).toEqual('info');
  });

  it('given stats is provided, returns stats', () => {
    // ACT
    const result = getInitialMobileTab('stats');

    // ASSERT
    expect(result).toEqual('stats');
  });

  it('given an invalid tab is provided, returns achievements', () => {
    // ACT
    const result = getInitialMobileTab('invalid-tab');

    // ASSERT
    expect(result).toEqual('achievements');
  });
});
