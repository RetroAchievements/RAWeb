/**
 * Updates a query parameter in the current URL and navigates to the new URL.
 *
 * @param paramName - The name of the query parameter to update.
 * @param newQueryParamValue - The new value for the query parameter.
 */
export function updateUrlParameter(
  paramName: string | string[],
  newQueryParamValue: string | string[],
) {
  const url = new URL(window.location.href);
  const params = new URLSearchParams(url.search);

  const paramNames = Array.isArray(paramName) ? paramName : [paramName];
  const newQueryParamValues = Array.isArray(newQueryParamValue)
    ? newQueryParamValue
    : [newQueryParamValue];

  if (paramNames.length !== newQueryParamValues.length) {
    throw new Error('paramName and newQueryParamValue arrays must be of equal length.');
  }

  for (let i = 0; i < paramNames.length; i += 1) {
    const currentParamName = paramNames[i];
    const currentNewQueryParamValue = newQueryParamValues[i];

    params.set(currentParamName, currentNewQueryParamValue);
  }

  url.search = params.toString();
  window.location.href = url.toString();
}
