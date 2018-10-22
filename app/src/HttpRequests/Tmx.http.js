import axios, {post, get} from 'axios';
import qs from "qs";

/**
 *
 * @return {Promise}
 */
export const httpGetTmxList = () => {
    const url = '/plugins/aligner/tm/mine';
    return get(url)
};

export const httpCreateTmx = () => {
    const url = '/plugins/aligner/tm/create_key';
    return post(url)
};

export const httpSaveTmx = (key, name) => {
    const url = '/plugins/aligner/tm/' + key + '/save';
    return post(url, qs.stringify({name: name}))
};


