import React from 'react';
import type { ServerDomain } from '../../api/api.types';
import { Badge, IconButton } from '../../framework/ui';
import { useCopied } from '../../hooks/useCopied';
import { Host, Row } from './ServerDomainsCard.styles';

export const DomainRow = ({ domain }: { domain: ServerDomain }) => {
    const { copied, copy } = useCopied();

    return (
        <Row>
            <Host title={domain.host}>{domain.host}</Host>
            {domain.wildcard && <Badge $tone='accent'>Any subdomain</Badge>}
            {domain.status !== 'active' && <Badge $tone='warn'>Being fixed</Badge>}
            <IconButton icon={copied ? 'check' : 'copy'} label={copied ? 'Copied' : 'Copy address'} onClick={() => copy(domain.host)} />
        </Row>
    );
};
