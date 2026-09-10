import { useCallback, useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import * as ImagePicker from 'expo-image-picker';

import { api } from '../api/jambo';
import type { Profile } from '../api/endpoints';
import { ApiError, NetworkError } from '../api/errors';
import { avatarFileName, imageMimeType } from './profileFields';

/**
 * Choosing a profile picture, once, for every screen that offers it.
 *
 * 🔴 **Rio, 2026-09-10: the profile screens carry "fake information" — copy
 * that sends people to the website for things the app does.** The editor's
 * clearest example was its avatar caption, *"Profile pictures are changed on
 * the Jambo website"*, which was false in the plainest way: `ProfileScreen`,
 * one tap away, has had a working picker and upload for days. The app was
 * telling somebody to open a browser for something it does itself.
 *
 * The fix could not be to copy that flow into the editor. It is permission
 * handling, a picker, MIME sniffing, an optimistic-update decision and four
 * distinct failure sentences — the exact shape that becomes two subtly
 * different implementations within a week. So it moved here and both screens
 * call it.
 *
 * **Nothing about the behaviour changed in the move**, including the two
 * decisions worth keeping:
 *
 * - **No optimistic update.** An upload takes seconds on a Ugandan uplink and
 *   can fail on size or type. Showing the new face before the server has it
 *   means silently swapping it back, which reads as the app losing the photo.
 * - **A refusal is a sentence, not silence.** A picker that simply never opens
 *   is the most common way this breaks, and the viewer cannot guess why — so
 *   the two refusals are told apart, because "turn it on in settings" is
 *   useless advice to somebody who was never asked.
 */
export function useAvatarUpload() {
  const queryClient = useQueryClient();

  const [error, setError] = useState<string | null>(null);

  const upload = useMutation({
    mutationFn: (file: { uri: string; name: string; type: string }) => api.uploadAvatar(file),
    onSuccess: (updated: Profile) => {
      setError(null);
      queryClient.setQueryData(['profile'], updated);
    },
    onError: (caught) => {
      setError(
        caught instanceof NetworkError
          ? 'Could not reach Jambo, so your photo was not changed.'
          : caught instanceof ApiError
            ? caught.message
            : 'That photo could not be uploaded. Try another one.',
      );
    },
  });

  const pick = useCallback(async () => {
    setError(null);

    const permission = await ImagePicker.requestMediaLibraryPermissionsAsync();

    if (!permission.granted) {
      setError(
        permission.canAskAgain
          ? 'Jambo needs access to your photos to change your picture.'
          : 'Photo access is turned off for Jambo. Turn it on in Android settings to change your picture.',
      );
      return;
    }

    const picked = await ImagePicker.launchImageLibraryAsync({
      mediaTypes: ['images'],
      allowsEditing: true,
      // Drawn in a circle everywhere it appears, so it is cropped square here
      // rather than centre-cropped later at every size.
      aspect: [1, 1],
      // The endpoint caps at 2 MB and a modern phone camera clears that on its
      // own. Compressing here is what stops a good photo being refused for
      // being large, on a connection where the upload was the slow part.
      quality: 0.8,
    });

    if (picked.canceled) return;

    const asset = picked.assets[0];
    if (asset === undefined) return;

    const type = imageMimeType(asset.uri, asset.mimeType);

    upload.mutate({ uri: asset.uri, name: avatarFileName(type), type });
  }, [upload]);

  return { pick, busy: upload.isPending, error, clearError: () => setError(null) };
}
