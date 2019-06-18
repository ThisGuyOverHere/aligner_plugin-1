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

// used for logged user who chooses the private txm option
export const httpExportTmxPrivate = (key) => {
    const url = '/plugins/aligner/job/' + ProjectStore.jobID + '/' + ProjectStore.jobPassword + '/tm/' + key + '/push_tmx';
    return post(url, qs.stringify({is_public: 0}))
};

// used for logged user who chooses the public cloud option
export const httpExportTmxCloud = () => {
    const url = '/plugins/aligner/job/' + ProjectStore.jobID + '/' + ProjectStore.jobPassword + '/push_tmx';
    return post(url, qs.stringify({is_public: 1}))
};

// used for not logged user ( temporary always public )
export const httpExportTmxFile = (email) => {
    const url = '/plugins/aligner/job/' + ProjectStore.jobID + '/' + ProjectStore.jobPassword + '/push_tmx';
    return post(url, qs.stringify({email: email, is_public: 1}))
};

