import { getIsInteractiveElement } from './getIsInteractiveElement';

describe('Util: getIsInteractiveElement', () => {
  it('is defined', () => {
    // ASSERT
    expect(getIsInteractiveElement).toBeDefined();
  });

  it('given a non-React element, returns false', () => {
    // ARRANGE
    const notAnElement = 'just a string';

    // ACT
    const result = getIsInteractiveElement(notAnElement);

    // ASSERT
    expect(result).toEqual(false);
  });

  it('given a native button element, returns true', () => {
    // ARRANGE
    const buttonElement = <button>Click me</button>;

    // ACT
    const result = getIsInteractiveElement(buttonElement);

    // ASSERT
    expect(result).toEqual(true);
  });

  it('given a native div element, returns false', () => {
    // ARRANGE
    const divElement = <div>Content</div>;

    // ACT
    const result = getIsInteractiveElement(divElement);

    // ASSERT
    expect(result).toEqual(false);
  });

  it('given a custom interactive component, returns true', () => {
    // ARRANGE
    const BaseButton = () => <button>Click</button>;
    BaseButton.displayName = 'BaseButton';
    const element = <BaseButton />;

    // ACT
    const result = getIsInteractiveElement(element);

    // ASSERT
    expect(result).toEqual(true);
  });

  it('given an element with an href prop, returns true', () => {
    // ARRANGE
    const element = <a href="/some-path">Click me</a>;

    // ACT
    const result = getIsInteractiveElement(element);

    // ASSERT
    expect(result).toEqual(true);
  });

  it('given a component without an explicit displayName or name, returns false', () => {
    // ARRANGE
    const AnonymousComponent = () => <div>Content</div>;
    const element = <AnonymousComponent />;

    // ACT
    const result = getIsInteractiveElement(element);

    // ASSERT
    expect(result).toEqual(false);
  });
});
