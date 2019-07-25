import axios, {post, get} from 'axios';
import qs from 'qs';
import env from "../Constants/Env.constants";

/**
 *
 * @param data
 * @param {String} data.file_name
 * @param {String} data.source_lang
 * @param {String} data.target_lang
 * @return {Promise}
 */
export const httpConversion = (data) => {
	const url = '/plugins/aligner/xliff_conversion';
	return post(url, qs.stringify(data));
};
/**
 *
 * @param data
 * @param {String} data.project_name
 * @param {String} data.file_name_source
 * @param {String} data.file_name_target
 * @param {String} data.source_lang
 * @param {String} data.target_lang
 * @return {Promise}
 */
export const httpCreateProject = (data) => {
	const url = '/plugins/aligner/create_project';
	return post(url, qs.stringify(data));
};
/**
 *
 * @param {Number} jobID The job to align
 * @return {Promise}
 */
export const httpAlignJob = (jobID) => {
	const url = '/plugins/aligner/parse/' + jobID;
	return get(url);
};


export const httpGetSegments = (jobID, jobPassword) => {
	const url = '/plugins/aligner/job/' + jobID + '/' + jobPassword + '/segments';
	return get(url);
};

/**
 *
 * @param {Number} jobID
 * @param {String} jobPassword
 * @param {Object} data
 * @param {String} data.type
 * @param {Number} data.order
 * @param {Number} data.inverseOrder
 * @param {Array} data.positions
 * @return {*}
 */
export const httpSplitSegment = (jobID, jobPassword, data) => {
	const url = '/plugins/aligner/job/' + jobID + '/' + jobPassword + '/segment/split';
	const values = {
		type: data.type,
		order: data.order,
		inverse_order: data.inverseOrder,
		positions: data.positions
	};
	return post(url, qs.stringify(values));
};

/**
 *
 * @param jobID
 * @param jobPassword
 * @returns {*}
 */
export const httpGetAlignmentInfo = (jobID, jobPassword) => {
	const url = '/plugins/aligner/job/' + jobID + '/' + jobPassword + '/information';
	return get(url);
};

export const httpGetPullingInfo = (jobID, jobPassword) => {
	const url = '/plugins/aligner/job/' + jobID + '/' + jobPassword + '/check_progress';
	return get(url);
};

/**
 *
 * @param {Number} jobID
 * @param {String} jobPassword
 * @param {Object} data
 * @param {String} data.type
 * @param {Array} data.orders
 * @param {Number} data.orders[]
 * @return {*}
 */
export const httpMergeSegments = (jobID, jobPassword, data) => {
	const url = '/plugins/aligner/job/' + jobID + '/' + jobPassword + '/segment/merge';
	return post(url, qs.stringify(data));
};

export const httpMergeAlignSegments = (jobID, jobPassword, matches, inverses, destination) => {
	const url = '/plugins/aligner/job/' + jobID + '/' + jobPassword + '/segment/merge_align';
	return post(url, qs.stringify({matches: matches, inverses: inverses, destination: destination}));
};

export const httpMoveSegments = (jobID, jobPassword, data) => {
	const url = '/plugins/aligner/job/' + jobID + '/' + jobPassword + '/segment/move';
	return post(url, qs.stringify(data));
};

export const httpHideSegments = (jobID, jobPassword, data) => {
	const url = '/plugins/aligner/job/' + jobID + '/' + jobPassword + '/segment/hide';
	return post(url, qs.stringify({matches: data}));
};

export const httpShowSegments = (jobID, jobPassword, data) => {
	const url = '/plugins/aligner/job/' + jobID + '/' + jobPassword + '/segment/show';
	return post(url, qs.stringify({matches: data}));
};

export const httpDeleteSegments = (jobID, jobPassword, data) => {
	const url = '/plugins/aligner/job/' + jobID + '/' + jobPassword + '/segment/delete';
	return post(url, qs.stringify({matches: data}));
};

export const httpReverseSegments = (jobID, jobPassword, data) => {
	const url = '/plugins/aligner/job/' + jobID + '/' + jobPassword + '/segment/switch';
	return post(url, qs.stringify(data));
};

/**
 *
 * @param {Number} jobID
 * @param {String} jobPassword
 * @param {Object} data
 * @param {String} data.operation
 * @return {*}
 */
export const httpUndoChanges = (jobID, jobPassword, data) => {
	const url = `/plugins/aligner/job/${jobID}/${jobPassword}/segment/undo/${data.operation}`;
	return post(url, qs.stringify(data));
};
