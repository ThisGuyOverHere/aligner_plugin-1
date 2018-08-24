import axios, {post} from 'axios';

/**
 *
 * @param file
 * @param onProgress function for onProgress upload
 * @return {Promise}
 */
export const httpUpload = (file, onProgress) => {
    const url = '/plugins/aligner/upload';
    const formData = new FormData();
    formData.append('files', file);
    let config = {
        headers: {
            'content-type': 'multipart/form-data'
        }
    };
    if(onProgress){
        config.onUploadProgress = onProgress;
    }
    return post(url, formData, config);
};