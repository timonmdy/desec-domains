import http, { httpErrorToHuman } from '@/api/http';
import { APP } from '../config/app.config';

const url = (serverUuid: string, path: string) => `${APP.apiBase}/${serverUuid}${path}`;

export const apiGet = async <T>(serverUuid: string, path: string): Promise<T> => {
    const response = await http.get<T>(url(serverUuid, path));

    return response.data;
};

export const errorMessage = (error: unknown): string => httpErrorToHuman(error);
