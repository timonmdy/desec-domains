import React from 'react';
import { ServerContext } from '@/state/server';
import { ThemeScope } from '../../framework/theme';
import { Icon } from '../../framework/ui';
import { DomainRow } from './DomainRow';
import { Card, List, Title } from './ServerDomainsCard.styles';
import { useServerDomains } from './useServerDomains';

const ServerDomainsCard = () => {
    const uuid = ServerContext.useStoreState((state) => state.server.data?.uuid);
    const domains = useServerDomains(uuid);

    if (domains.length === 0) return null;

    return (
        <ThemeScope>
            <Card aria-label='Server domains'>
                <Title>
                    <Icon name='globe' fixedWidth /> Server address
                </Title>
                <List>
                    {domains.map((domain) => (
                        <DomainRow key={domain.id} domain={domain} />
                    ))}
                </List>
            </Card>
        </ThemeScope>
    );
};

export default ServerDomainsCard;
