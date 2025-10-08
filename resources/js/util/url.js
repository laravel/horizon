import { identity, pickBy } from 'lodash-es';

/**
 * @param {string} base
 * @param {string} path
 * @param {any} parameters
 * @returns {string}
 */
export default function url(base, path, parameters) {
    let searchParams = new URLSearchParams(pickBy(parameters || {}, identity));

    let queryString = searchParams.toString();

    if (base == '/' && path.startsWith('/')) {
      base = '';
    }

    return base + path + (queryString.length > 0 ? `?${queryString}` : '');
}
