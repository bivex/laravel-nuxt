/**
 * Copyright (c) 2025 Bivex
 *
 * Author: Bivex
 * Available for contact via email: support@b-b.top
 * For up-to-date contact information:
 * https://github.com/bivex
 *
 * Created: 2025-12-25T11:55:40
 * Last Updated: 2025-12-25T11:55:40
 *
 * Licensed under the MIT License.
 * Commercial licensing available upon request.
 */

import type { HttpUseFetchOptions } from '~';

function createHttpComposable<T>(
  url: string | (() => string),
  options?: HttpUseFetchOptions<T>,
  lazy: boolean = false,
) {
  const { $http } = useNuxtApp();

  return lazy
    ? useLazyFetch<T>(url, { ...options, $fetch: $http } as any)
    : useFetch<T>(url, { ...options, $fetch: $http } as any);
}

export function useHttp<T>(
  url: string | (() => string),
  options?: HttpUseFetchOptions<T>,
) {
  return createHttpComposable<T>(url, options, false);
}

export function useLazyHttp<T>(
  url: string | (() => string),
  options?: HttpUseFetchOptions<T>,
) {
  return createHttpComposable<T>(url, options, true);
}