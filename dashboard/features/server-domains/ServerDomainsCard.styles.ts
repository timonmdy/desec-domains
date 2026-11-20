import styled from 'styled-components';
import { color, radius } from '../../framework/theme';

export const Card = styled.section`
    display: block;
    width: 100%;
    min-width: 0;
    max-width: 100%;
    flex: 1 1 100%;
    align-self: stretch;
    grid-column: 1 / -1;
    margin-top: 12px;
    padding: 14px 16px;
    background: ${color.surface};
    border: 1px solid ${color.border};
    border-radius: ${radius.md};
`;

export const Title = styled.h3`
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0 0 10px;
    color: ${color.text};
    font-size: 13px;
    font-weight: 600;
    white-space: nowrap;
`;

export const List = styled.ul`
    display: grid;
    gap: 6px;
    margin: 0;
    padding: 0;
    list-style: none;
`;

export const Row = styled.li`
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: 0;
    padding: 6px 6px 6px 10px;
    background: ${color.raised};
    border: 1px solid ${color.border};
    border-radius: ${radius.sm};
`;

export const Host = styled.code`
    flex: 1;
    min-width: 0;
    overflow: hidden;
    color: ${color.text};
    font-size: 13px;
    text-overflow: ellipsis;
    white-space: nowrap;
`;
