/**
 * This file provides a factory helper for creating test data, with a specific
 * use case of colocating the factory within the model file itself.
 * It's designed to:
 *   1. Only load faker in test environments to avoid bloating production bundles.
 *   2. Provide a synchronous API for better developer experience.
 *   3. Ensure faker is loaded only once for performance.
 */

// eslint-disable-next-line @typescript-eslint/consistent-type-imports -- this is a valid use case
type Faker = typeof import('@faker-js/faker').faker;
type FactoryFunction<T> = (props?: Partial<T>) => T;

// We use a module-level variable to ensure faker is only loaded once.
let fakerInstance: Faker | null = null;

/**
 * Loads the faker library asynchronously and caches the instance.
 * This should be called in test setup before any factories are used,
 * ideally within a `beforeAll()` lifecycle hook.
 *
 * @returns A promise that resolves to the faker instance.
 */
export async function loadFaker(): Promise<Faker> {
  if (!fakerInstance) {
    // Use a dynamic import to ensure faker is not included in production bundles.
    const { faker } = await import('@faker-js/faker');
    fakerInstance = faker;
  }

  return fakerInstance;
}

/**
 * Create a factory function for generating test data.
 *
 * @param defaultProps A function that takes faker and returns default properties for the model.
 * @returns A factory function that can be used to create test data.
 *
 * This approach allows us to define factories alongside our models for better co-location.
 * It provides a synchronous API for creating test data, and ensures faker is only loaded
 * in test environments, keeping production bundles lean. It also allows for partial overrides
 * of generated data for flexibility in test cases.
 */
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
    // In non-test environments, we return a function that immediately throws to prevent accidental use.
    return (): never => {
      throw new Error('Factories are not available outside of the test environment.');
    };
  }
}
