import axios, {post, get} from 'axios';

import qs from "qs";
import ProjectStore from "../Stores/Project.store";

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


export const httpExportTmxCloud = (key, isPrivate) => {
    const url = '/plugins/aligner/job/' + ProjectStore.jobID + '/' + ProjectStore.jobPassword + '/tm/' + key + '/push_tmx';
    return post(url, qs.stringify({isPrivate: isPrivate}))
};

export const httpExportTmxFile = (email, isPrivate) => {
    const url = '/plugins/aligner/job/' + ProjectStore.jobID + '/' + ProjectStore.jobPassword + '/push_tmx';
    return post(url, qs.stringify({email: email, isPrivate: isPrivate}))
};
