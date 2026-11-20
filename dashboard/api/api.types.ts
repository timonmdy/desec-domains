export type AllocationKind = 'domain' | 'subdomain';

export interface ServerDomain {
    id: number;
    host: string;
    kind: AllocationKind;
    wildcard: boolean;
    status: 'active' | 'error';
}

export interface ServerDomainsResponse {
    domains: ServerDomain[];
}
