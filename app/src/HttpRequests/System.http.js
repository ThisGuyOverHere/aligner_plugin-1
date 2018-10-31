import axios, {post,get} from 'axios';
import qs from 'qs';
import env from "../Constants/Env.constants";

/**
 *
 * @param data
 * @param {String} data.email
 * @param {String} data.password
 * @return {Promise}
 */
export const httpLogin = (data) => {
    const url = '/api/app/user/login';
    return post(url, qs.stringify(data));
};

/**
 *
 * @return {Promise}
 */
export const httpConfig = () => {
    const url = '/plugins/aligner/configs';
    return get(url);
};

/**
 *
 * @return {Promise}
 */
export const httpMe = () => {
    const url = '/api/app/user';
    return get(url);
};

/**
 *
 * @return {Promise}
 */
export const httpLogout = () => {
    const url = '/api/app/user/logout';
    return post(url , null);
};
