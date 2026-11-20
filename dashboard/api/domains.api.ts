import type { ServerDomain, ServerDomainsResponse } from './api.types';
import { apiGet } from './client';

export const fetchServerDomains = async (serverUuid: string): Promise<ServerDomain[]> =>
    (await apiGet<ServerDomainsResponse>(serverUuid, '/domains')).domains;
