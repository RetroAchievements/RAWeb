// `__UNSAFE_PaginatedData` leaves the `items` array untyped.
// We'll use this wrapper to enforce strong types.

export interface PaginatedData<T> extends Omit<App.Data.__UNSAFE_PaginatedData, 'items'> {
  items: T[];
}

/**
 * Creates a PaginatedData object with the given items.
 *
 * @template T - The type of the items in the paginated data.
 * @param {T[]} items - An array of items to include in the paginated data.
 * @returns {PaginatedData<T>} The paginated data object containing the provided items.
 *
 * @example
 * // Create an array of User items
 * const users: App.Data.User[] = [
 *   createUser(),
 *   createUser(),
 * ];
 *
 * // Create paginated data for the users
 * const paginatedUsers = createPaginatedData(users);
 * console.log(paginatedUsers);
 * // Output:
 * // {
 * //   currentPage: 1,
 * //   items: [... created users ...],
 * //   lastPage: 9999,
 * //   links: { first: null, last: null, next: null, previous: null },
 * //   perPage: 25,
 * //   total: 25000
 * // }
 */
export const createPaginatedData = <T>(
  items: T[],
  overrides?: Partial<PaginatedData<T>>,
): PaginatedData<T> => {
  return {
    currentPage: 1,
    items,
    lastPage: 9999,
    links: {
      firstPageUrl: null,
      lastPageUrl: null,
      nextPageUrl: null,
      previousPageUrl: null,
    },
    perPage: 25,
    total: 25000,

    ...overrides,
  };
};
