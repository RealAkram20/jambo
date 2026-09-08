type Listener = () => void;

const listeners = new Set<Listener>();

/**
 * Fired when the server rejects the token this app is holding.
 *
 * The path this exists for is real and not rare: a viewer boots this phone
 * from the website's device list at `/{username}/devices`, or from another
 * handset. The token is deleted server-side, and the next request this app
 * makes — any request — comes back 401. Without this, the app sits on a
 * signed-in shell full of screens that all quietly fail.
 *
 * `ApiClient` has no idea React exists, so it calls `onUnauthenticated` and
 * this carries it to the provider.
 */
export function onUnauthenticated(listener: Listener): () => void {
  listeners.add(listener);
  return () => listeners.delete(listener);
}

export function emitUnauthenticated(): void {
  for (const listener of listeners) listener();
}
