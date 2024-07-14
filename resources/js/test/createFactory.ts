// eslint-disable-next-line @typescript-eslint/consistent-type-imports
type Faker = typeof import('@faker-js/faker').faker;
type FactoryFunction<T> = (props?: Partial<T>) => T;

let fakerInstance: Faker | null = null;

export async function loadFaker(): Promise<Faker> {
  if (!fakerInstance) {
    const { faker } = await import('@faker-js/faker');
    fakerInstance = faker;
  }

  return fakerInstance;
}

export function createFactory<T>(defaultProps: (faker: Faker) => T): FactoryFunction<T> {
  if (process.env.NODE_ENV === 'test') {
    return (props?: Partial<T>): T => {
      if (!fakerInstance) {
        throw new Error('Faker not initialized. Call initializeFaker() before using factories.');
      }

      return {
        ...defaultProps(fakerInstance),
        ...props,
      };
    };
  } else {
    return (): never => {
      throw new Error('Factories are not available outside of the test environment.');
    };
  }
}
