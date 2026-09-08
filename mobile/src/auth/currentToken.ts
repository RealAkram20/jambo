/**
 * The bearer token, held outside React.
 *
 * `ApiClient` needs the token synchronously, at the moment it builds a
 * request, and it is constructed once for the life of the app. Reading it out
 * of React state would mean rebuilding the client whenever the token changed —
 * and any request already in flight would still be carrying the old one.
 *
 * `AuthProvider` owns writing to this. Nothing else should.
 */
let token: string | null = null;

export function getToken(): string | null {
  return token;
}

export function setToken(next: string | null): void {
  token = next;
}
