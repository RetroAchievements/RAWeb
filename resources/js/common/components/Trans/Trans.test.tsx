import { render, screen, within } from '@/test';

import { Trans } from './Trans';

const MockT = vi.fn();

vi.mock('./useMockableLaravelReactI18n', () => ({
  useMockableLaravelReactI18n: () => ({
    loading: false,
    currentLocale: () => 'en_US',
    t: MockT,
  }),
}));

describe('Component: Trans', () => {
  it('renders without crashing', () => {
    // ARRANGE
    MockT.mockImplementationOnce(() => 'Welcome, John!');

    const name = 'John';

    const { container } = render(
      <Trans i18nKey="Welcome, :name!" values={{ name }}>
        Welcome, {name}
      </Trans>,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('can perform basic interpolation', () => {
    // ARRANGE
    MockT.mockImplementationOnce(() => 'Welcome, John!');

    const name = 'John';

    render(
      <Trans i18nKey="Welcome, :name" values={{ name }}>
        Welcome, {name}
      </Trans>,
    );

    // ASSERT
    expect(screen.getByText(/welcome, john/i)).toBeVisible();
  });

  it('can interpolate with nested elements', () => {
    // ARRANGE
    MockT.mockImplementationOnce(() => "Welcome to <0>John</0>'s profile");

    const name = 'John';

    render(
      <p>
        <Trans i18nKey="Welcome to <0>:name</0>'s profile" values={{ name }}>
          Welcome to <strong>{name}</strong>'s profile
        </Trans>
      </p>,
    );

    // ASSERT
    const paragraphEl = screen.getByText(/welcome to/i).closest('p');
    const strongEl = within(paragraphEl!).getByText(name);

    expect(paragraphEl).toBeVisible();
    expect(strongEl.tagName).toBe('STRONG');
    expect(strongEl).toBeVisible();

    expect(paragraphEl).toHaveTextContent(`Welcome to ${name}'s profile`);
  });

  it('can interpolate a singular count', () => {
    // ARRANGE
    const count = 1;

    render(
      <p>
        <Trans i18nKey="messages" count={count}>
          You have <strong>{count}</strong> unread messages
        </Trans>
      </p>,
    );

    // ASSERT
    expect(MockT).toHaveBeenCalledOnce();
    expect(MockT).toHaveBeenCalledWith('messages_one', { count: 1 });
  });

  it('can interpolate a plural count', () => {
    // ARRANGE
    const count = 5;

    render(
      <p>
        <Trans i18nKey="messages" count={count}>
          You have <strong>{count}</strong> unread messages
        </Trans>
      </p>,
    );

    // ASSERT
    expect(MockT).toHaveBeenCalledOnce();
    expect(MockT).toHaveBeenCalledWith('messages_other', { count: 5 });
  });

  it('can interpolate with multiple elements', () => {
    // ARRANGE
    MockT.mockImplementationOnce(
      () => 'Welcome back <0>John</0>! Your last login was <1>2:30 PM</1>.',
    );

    const name = 'John';
    const time = '2:30 PM';

    render(
      <p>
        <Trans
          i18nKey="Welcome back <0>:name</0>! Your last login was <1>:time</1>."
          values={{ name, time }}
        >
          Welcome back <strong>{name}</strong>! Your last login was <em>{time}</em>
        </Trans>
      </p>,
    );

    // ASSERT
    const paragraphEl = screen.getByText(/welcome back/i).closest('p');

    const strongEl = within(paragraphEl!).getByText(name);
    expect(strongEl.tagName).toBe('STRONG');
    expect(strongEl).toBeVisible();

    const emEl = within(paragraphEl!).getByText(time);
    expect(emEl.tagName).toBe('EM');
    expect(emEl).toBeVisible();

    expect(paragraphEl).toHaveTextContent(`Welcome back ${name}! Your last login was ${time}`);
  });

  it('can interpolate with nested elements', () => {
    // ARRANGE
    MockT.mockImplementationOnce(() => 'Welcome <0>John (<1>3</1>)</0>');

    const name = 'John';
    const count = 3;

    render(
      <p>
        <Trans i18nKey="Welcome <0>:name (<1>:count</1>)</0>" values={{ name, count }}>
          Welcome{' '}
          <strong>
            {name} (<em>{count}</em>)
          </strong>
        </Trans>
      </p>,
    );

    // ASSERT
    const paragraphEl = screen.getByText(/welcome/i).closest('p');

    const strongEl = within(paragraphEl!).getByText(new RegExp(name, 'i'));
    expect(strongEl.tagName).toBe('STRONG');
    expect(strongEl).toBeVisible();

    const emEl = within(strongEl).getByText(count.toString());
    expect(emEl.tagName).toBe('EM');
    expect(emEl).toBeVisible();

    expect(paragraphEl).toHaveTextContent(`Welcome ${name} (${count})`);
  });

  it('can perform multiple interpolations within the same element', () => {
    // ARRANGE
    MockT.mockImplementationOnce(() => 'Progress: <0>80/100</0>');

    const completed = 80;
    const total = 100;

    render(
      <p>
        <Trans i18nKey="Progress: <0>:completed/:total</0>" values={{ completed, total }}>
          Progress:{' '}
          <strong>
            {completed}/{total}
          </strong>
        </Trans>
      </p>,
    );

    // ASSERT
    const paragraphEl = screen.getByText(/progress:/i).closest('p');

    const strongEl = within(paragraphEl!).getByText(`${completed}/${total}`);
    expect(strongEl.tagName).toBe('STRONG');
    expect(strongEl).toBeVisible();

    expect(paragraphEl).toHaveTextContent(`Progress: ${completed}/${total}`);
  });

  it('handles missing elements by falling back to children', () => {
    // ARRANGE
    MockT.mockImplementationOnce(() => 'Hello <1>World</1>!');

    const name = 'World';

    render(
      <p>
        <Trans i18nKey="Hello <1>:name</1>">
          Hello <strong>{name}</strong>!
        </Trans>
      </p>,
    );

    // ASSERT
    const paragraphEl = screen.getByText(/hello/i).closest('p');
    expect(paragraphEl).toHaveTextContent(`Hello ${name}!`);
  });
});
