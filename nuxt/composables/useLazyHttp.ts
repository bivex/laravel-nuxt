/**
 * Copyright (c) 2025 Bivex
 *
 * Author: Bivex
 * Available for contact via email: support@b-b.top
 * For up-to-date contact information:
 * https://github.com/bivex
 *
 * Created: 2025-12-25T11:55:41
 * Last Updated: 2025-12-25T11:56:28
 *
 * Licensed under the MIT License.
 * Commercial licensing available upon request.
 */

import type { HttpUseFetchOptions } from '~';

export function useLazyHttp<T>(
  url: string | (() => string),
  options?: HttpUseFetchOptions<T>,
) {
  return useLazyFetch<T>(url, options as any);
}