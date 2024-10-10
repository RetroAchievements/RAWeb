import { updateUrlParameter } from './updateUrlParameter';

describe('Util: updateUrlParameter', () => {
  let originalUrl: string;

  beforeEach(() => {
    originalUrl = window.location.href;

    Object.defineProperty(window, 'location', {
      writable: true,
      value: { href: 'http://localhost?param1=oldValue1&param2=oldValue2' },
    });
  });

  afterEach(() => {
    window.location.href = originalUrl;
  });

  it('is defined', () => {
    // ASSERT
    expect(updateUrlParameter).toBeDefined();
  });

  it('can update a single query parameter', () => {
    // ACT
    updateUrlParameter('param1', 'newValue1');

    // ASSERT
    expect(window.location.href).toEqual('http://localhost/?param1=newValue1&param2=oldValue2');
  });

  it('can simultaneously update multiple query parameters', () => {
    // ACT
    updateUrlParameter(['param1', 'param2'], ['newValue1', 'newValue2']);

    // ASSERT
    expect(window.location.href).toEqual('http://localhost/?param1=newValue1&param2=newValue2');
  });

  it('given a null value for a query param, deletes the param from the url', () => {
    // ACT
    updateUrlParameter('param1', null);

    // ASSERT
    expect(window.location.href).toEqual('http://localhost/?param2=oldValue2');
  });

  it('throws an error if paramName and newQueryParamValue arrays are not of equal length', () => {
    // ASSERT
    expect(() => {
      updateUrlParameter(['one'], ['one', 'two']);
    }).toThrow();
  });
});
