export function fetcher<T>(
  requestUrl: string,
  fetchOptions?: Partial<{
    method: 'GET' | 'POST';
    body: BodyInit;
    credentials: RequestCredentials;
    headers: HeadersInit;
  }>
): Promise<T> {
  const csrfToken = document
    .querySelector('meta[name="csrf-token"]')
    ?.getAttribute('content');

  const modifiedFetchOptions: RequestInit = {
    ...fetchOptions,
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
      'X-CSRF-TOKEN': csrfToken ?? '',
      ...fetchOptions?.headers,
    },
  };

  return fetch(requestUrl, modifiedFetchOptions).then((response) => {
    if (!response.ok) {
      throw new Error(JSON.stringify(response));
    }

    return response.json() as Promise<T>;
  });
}
