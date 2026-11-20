import { useEffect, useState } from 'react';
import type { ServerDomain } from '../../api/api.types';
import { fetchServerDomains } from '../../api/domains.api';

/** Loads once per console visit. The endpoint reads our own database, so no polling is needed. */
export const useServerDomains = (uuid: string | undefined) => {
    const [domains, setDomains] = useState<ServerDomain[]>([]);

    useEffect(() => {
        if (!uuid) return;
        let cancelled = false;

        fetchServerDomains(uuid)
            .then((list) => !cancelled && setDomains(list))
            // The console page must never break because of this extension, so a failure just hides the card.
            .catch((error: unknown) => console.warn('[deSEC Domains] Could not load the server domains.', error));

        return () => {
            cancelled = true;
        };
    }, [uuid]);

    return domains;
};
