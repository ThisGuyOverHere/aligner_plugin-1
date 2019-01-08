import {
    httpDeleteSegments, httpHideSegments, httpMergeAlignSegments,
    httpMergeSegments,
    httpMoveSegments,
    httpReverseSegments, httpShowSegments
} from "../HttpRequests/Alignment.http";
import ProjectActions from "../Actions/Project.actions";
import SystemActions from "../Actions/System.actions";
import ReactGA from "react-ga";

/**
 *
 * @param text
 * @returns {string} with a centerd ellipsis
 */
export const textEllipsisCenter = (text) => {
    if (text.length > 26) {
        return text.substr(0, 13) + '[...]' + text.substr(text.length - 6, text.length);
    }
    return text;
};

/**
 * google login function
 * @param url
 */
export const googleLogin = (url) => {
    let newWindow = window.open(url, 'name', 'height=600,width=900');
    if (window.focus) {
        newWindow.focus();
    }
    let interval = setInterval(function () {
        if (newWindow.closed) {
            SystemActions.checkUserStatus();
            SystemActions.setLoginStatus(false);
            SystemActions.setRegistrationStatus(false);
            clearInterval(interval);
        }
    }, 600);
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
            ReactGA.event({
                category: 'Interactions',
                action: method.data.jobID,
                label: 'merge',
            });
            httpMergeSegments(method.data.jobID, method.data.jobPassword, {
                order: method.data.order,
                type: method.data.type
            }).then((response) => {
                callback()
            }, (error) => {
                console.error(error);
            });
            break;
        case 'merge_align':
            ReactGA.event({
                category: 'Interactions',
                action: method.data.jobID,
                label: 'merge and align',
            });
            httpMergeAlignSegments(method.data.jobID, method.data.jobPassword, method.data.matches, method.data.destination).then((response) => {
                ProjectActions.requireDirectChangesToStore(response.data);
                callback()
            }, (error) => {
                console.error(error);
            });
            break;
        case 'align':
            ReactGA.event({
                category: 'Interactions',
                action: method.data.jobID,
                label: 'align',
            });
            httpMoveSegments(method.data.jobID, method.data.jobPassword, {
                order: method.data.order,
                type: method.data.type,
                destination: method.data.destination,
                inverse_destination: method.data.inverse_destination
            }).then((response) => {
                callback()
            }, (error) => {
                console.error(error);
            });
            break;
        case 'hide':
            httpHideSegments(method.data.jobID, method.data.jobPassword, method.data.matches)
                .then((response) => {
                    console.log(response);
                    ProjectActions.requireDirectChangesToStore(response.data);
                    callback()
                }, (error) => {
                    console.error(error);
                });
            break;
        case 'show':
            httpShowSegments(method.data.jobID, method.data.jobPassword, method.data.matches)
                .then((response) => {
                    console.log(response);
                    ProjectActions.requireDirectChangesToStore(response.data);
                    callback()
                }, (error) => {
                    console.error(error);
                });
            break;
        case 'reverse':
            ReactGA.event({
                category: 'Interactions',
                action: method.data.jobID,
                label: 'switch',
            });
            httpReverseSegments(method.data.jobID, method.data.jobPassword, method.data).then((response) => {
                callback()
            }, (error) => {
                console.error(error);
            });
            break;
        case 'delete':
            httpDeleteSegments(method.data.jobID, method.data.jobPassword, method.data.matches).then((response) => {
                callback()
            }, (error) => {
                console.error(error);
            });
            break;
    }
};


export const checkResultStore = (source, target) => {
    for (let x = 0; x < source.length - 1; x++) {
        if (source[x].next !== source[x + 1].order) {
            if (source[x - 1]) {
                console.log('[SOURCE: ' + parseInt(x - 1) + ']   ' + source[x - 1].order + '       ' + source[x - 1].next);
            }
            console.log('[SOURCE: ' + +x + ']   ' + source[x].order + '       ' + source[x].next);
            console.log('[SOURCE: ' + parseInt(x + 1) + ']   ' + source[x + 1].order + '       ' + source[x + 1].next);
        }
    }
    console.log('\n\n')
    for (let x = 0; x < target.length - 1; x++) {
        if (target[x].next !== target[x + 1].order) {
            if (target[x - 1]) {
                console.log('[TARGET: ' + parseInt(x - 1) + ']   ' + target[x - 1].order + '       ' + target[x - 1].next);
            }
            console.log('[TARGET: ' + +x + ']   ' + target[x].order + '       ' + target[x].next);
            console.log('[TARGET: ' + parseInt(x + 1) + ']   ' + target[x + 1].order + '       ' + target[x + 1].next);
        }
    }
    console.log('\n\n')
}
