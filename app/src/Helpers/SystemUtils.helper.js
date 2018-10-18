import {
    httpDeleteSegments,
    httpMergeSegments,
    httpMoveSegments,
    httpReverseSegments
} from "../HttpRequests/Alignment.http";

/**
 *
 * @param text
 * @returns {string} with a centerd ellipsis
 */
export const textEllipsisCenter = (text) => {
    if (text.length > 30) {
        return text.substr(0, 12) + '[...]' + text.substr(text.length - 12, text.length);
    }
    return text;
};

/**
 *
 * @param name
 * @param surname
 * @returns {string} the initials of user name
 */
export const getUserInitials = (name, surname) => {
    return name.split(" ").map((n) => n[0]).join("") + surname.split(" ").map((n) => n[0]).join("");
};

/**
 *
 * @param email
 * @returns {boolean} true if the email is a valid one, false otherwise
 */
export const emailValidator = (email) => {
    let re = /^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/;
    if (re.test(email.toLowerCase())) {
        return true;
    } else {
        return false;
    }
};


/**
 *
 * @param {Object} method
 * @param {Function} callback
 */
export const syncWithBackend = (method, callback) => {
    switch (method.action) {
        case 'merge':
            httpMergeSegments(method.data.jobID, method.data.jobPassword, {
                order: method.data.order,
                type: method.data.type
            }).then((response) => {
                callback()
            }, (error) => {
                console.log(error);
            });
            break;
        case 'align':
            httpMoveSegments(method.data.jobID, method.data.jobPassword, {
                order: method.data.order,
                type: method.data.type,
                destination: method.data.destination,
                inverse_destination: method.data.inverse_destination
            }).then((response) => {
                callback()
            }, (error) => {
                console.log(error);
            });
            break;
        case 'reverse':
            httpReverseSegments(method.data.jobID, method.data.jobPassword, method.data).then((response) => {
                callback()
            }, (error) => {
                console.log(error);
            });
            break;
        case 'delete':
            httpDeleteSegments(method.data.jobID, method.data.jobPassword, method.data.matches).then((response) => {
                callback()
            }, (error) => {
                console.log(error);
            });
            break;
    }
};


export const checkResultStore = (source, target) => {
    for (let x = 0; x < source.length - 1; x++) {
        if (source[x].next !== source[x + 1].order) {
            if (source[x - 1]) {
                console.log('[SOURCE: ' + parseInt(x-1) + ']   ' + source[x - 1].order + '       ' + source[x - 1].next);
            }
            console.log('[SOURCE: ' + +x + ']   ' + source[x].order + '       ' + source[x].next);
            console.log('[SOURCE: ' + parseInt(x+1) + ']   ' + source[x + 1].order + '       ' + source[x + 1].next);
            //console.log('Source index: ',x);
        }
        //console.log('['+x+']   '+source[x].order+'       '+source[x].next);
    }
    console.log('\n\n')
    for (let x = 0; x < target.length - 1; x++) {
        if (target[x].next !== target[x + 1].order) {
            if (target[x - 1]) {
                console.log('[TARGET: ' +parseInt(x-1) + ']   ' + target[x - 1].order + '       ' + target[x - 1].next);
            }
            console.log('[TARGET: ' + +x + ']   ' + target[x].order + '       ' + target[x].next);
            console.log('[TARGET: ' + parseInt(x+1) + ']   ' + target[x + 1].order + '       ' + target[x + 1].next);
            /*console.log('Target index: ',x);*/
        }
        //console.log('['+x+']   '+target[x].order+'       '+target[x].next);
    }
    console.log('\n\n')
}
